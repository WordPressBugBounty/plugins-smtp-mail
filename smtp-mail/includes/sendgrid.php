<?php
/*
 * Since 1.3
 */
defined('ABSPATH') or die();

function smtpmail_curl_send( $url = '', $post_data = array(), $headers = array() ) 
{
    if (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) == false) return false;
	
	$ch = curl_init();
	$timeout = 5;
    
	$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36 Edg/103.0.1264.77';

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

    if( count($headers)>0 ) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

	if( is_string($post_data) ) {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data );
	} else if( count($post_data)>0 ) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $post_data, '', '&') );
	}

    $response = curl_exec($ch);

    // Then, after your curl_exec call:
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    // $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

	curl_close($ch);

	return $body;
}

/**
 * https://docs.sendgrid.com/for-developers/sending-email/api-getting-started
 */
function smtpmail_curl_sendgrid( $args = array() ) 
{
    extract( shortcode_atts(array(
		'to' 	    => array(),
		'subject'   => '',
		'body' 		=> '',
	), (array) $args) );

    $sendgrid_api_key = smtpmail_options('sendgrid_api_key');

    if( $sendgrid_api_key == '' ) {
        $json = array(
            'code'      => 403,
            'message'   => 'Sendgrid API Key null'
        );
    } else {

        $from_email = smtpmail_options('From');
        $from_name  = smtpmail_options('FromName');
        
        if( smtpmail_options('IsHTML') == true ) {
            $content = [
                "type" => "text/html",
                "value" => wp_kses_post($body)
            ];
        } else {
            $content = [
                "type" => "text/plain",
                "value" => strip_tags($body)
            ];
        }
        
        // https://docs.sendgrid.com/api-reference/mail-send/mail-send
        $post_data = array(
            "personalizations" => [
                [
                    "to" => $to
                ]
            ],
            "from" => [
                "email" => $from_email,
                "name"  => $from_name,
            ],
            "subject" => "=?UTF-8?B?".base64_encode($subject)."?=",
            "content" => [ $content ],
        );
        
        $json = array(
            'code'      => 400,
            'message'   => 'Data fail'
        );

        $response = smtpmail_curl_send( 'https://api.sendgrid.com/v3/mail/send', json_encode($post_data), array(
            'Authorization: Bearer ' . $sendgrid_api_key,
            'Content-Type: application/json',
        ) );        
        
        if ( $response != '' ) {
            $response = json_decode($response, true);

            if( is_array($response) && isset($response['errors']) ) {
                $json['message'] = 'Sent fail';
            } else {
                $json = array(
                    'code'      => 200,
                    'message'   => 'Sent success'
                );
            }
        }
    }
    
    return $json;
}

function smtpmail_wp_mail_sendgrid( $result = null, $atts = array() ) 
{
    if( smtpmail_options('isSMTP') != 2 ) {
        return $result;
    }

    if ( isset( $atts['to'] ) ) {
        $to = $atts['to'];
    }

    if ( ! is_array( $to ) ) {
        $to = explode( ',', $to );
    }

    $addresses = [];

    foreach( $to as $i => $address ) {
        // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
        $recipient_name = '';

        if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
            if ( count( $matches ) == 3 ) {
                $recipient_name = $matches[1];
                $address        = $matches[2];
            }
        }

        if( $recipient_name == '' ) {
            $recipient_name = ucwords( explode('@',$address)[0] );
        }

        $addresses[] = [
            "email" => $address,
            "name"  => $recipient_name
        ];
    }

    if ( isset( $atts['subject'] ) ) {
        $subject = $atts['subject'];
    }

    if ( isset( $atts['message'] ) ) {
        $message = $atts['message'];
    }

    if ( isset( $atts['headers'] ) ) {
        $headers = $atts['headers'];
    }

    if ( isset( $atts['attachments'] ) ) {
        $attachments = $atts['attachments'];

        if ( ! is_array( $attachments ) ) {
            $attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
        }
    
        if ( ! empty( $attachments ) ) {
            foreach ( $attachments as $filename => $attachment ) {
                $filename = is_string( $filename ) ? $filename : '';
            }
        }
    }

    // setup data before send mail
    if( smtpmail_options('save_data') ) {
        $params = array_merge( $_POST, array(
            'ip' => $_SERVER['SERVER_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ) );

        $emails = smtpmail_array_values_by_key( $to, 'email' );
        $names = smtpmail_array_values_by_key( $to, 'name' );
        
        $send_data = array(
            'from_name' => smtpmail_options('FromName'),
            'from_email' => smtpmail_options('From'),
            'to_email' => implode(';', $emails),
            'to_name' => implode(';', $names),
            'message' => $message,
            'subject' => $subject,
            'params' => json_encode($params),
            'created' => current_time( 'mysql' ),
        );
        
        smtpmail_insert_data( $send_data );
    }
    
    $response = smtpmail_curl_sendgrid( array( 
        'to'        => $addresses,
        'subject'   => $subject,
        'body'      => $message,
    ) );
    
    $mail_data = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
    
    if( $response['code'] == 200 ) {
        do_action( 'wp_mail_succeeded', $mail_data );

        $result = true;
    } else {
        do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $response['message'], $mail_data ) );

        $result = false;
    }
    
    return $result;
}
add_filter('pre_wp_mail', 'smtpmail_wp_mail_sendgrid', 10, 2);
