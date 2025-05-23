<?php
defined('ABSPATH') or die;

/*************************** LOAD THE BASE CLASS *******************************
 *******************************************************************************
 * The WP_List_Table class isn't automatically available to plugins, so we need
 * to check if it's available and load it if necessary. In this tutorial, we are
 * going to use the WP_List_Table class directly from WordPress core.
 *
 * IMPORTANT:
 * Please note that the WP_List_Table class technically isn't an official API,
 * and it could change at some point in the distant future. Should that happen,
 * I will update this plugin with the most current techniques for your reference
 * immediately.
 *
 * If you are really worried about future compatibility, you can make a copy of
 * the WP_List_Table class (file path is shown just below) to use and distribute
 * with your plugins. If you do that, just remember to change the name of the
 * class to avoid conflicts with core.
 *
 * Since I will be keeping this tutorial up-to-date for the foreseeable future,
 * I am going to work with the copy of the class provided in WordPress core.
 */
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_Users_List_Table class.
 * WP_Users_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 * Our theme for this list table is going to be movies.
 */
class SMTPMail_Data_List_Table extends WP_List_Table
{

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct()
    {
        //Set parent defaults
        parent::__construct(array(
            'singular'  => 'customer',     //singular name of the listed records
            'plural'    => 'customers',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ));
    }

    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item = [], $column_name = '')
    {
        $value = isset($item[$column_name]) ? $item[$column_name] : '';

        switch ($column_name) {
            case 'status':
                return $value == 1 ? __('Success', 'smtp-mail') : __('Fail', 'smtp-mail');
            case 'message':
                return wp_trim_words($item[$column_name], 30);
            case 'location':
                $params = isset($item['params']) ? json_decode($item['params']) : false;

                if ($params && isset($params->ip)) {
                    return sprintf('<a href="%s" target="_blank" rel="help">%s</a>', esc_url('http://whois.photoboxone.com/location/' . $params->ip), __('View Map', 'smtp-mail'));
                }

                return '';
            case 'subject':
                return $this->column_title($item);
            default:
                return $value; //Show the whole array for troubleshooting purposes
        }
    }


    /** ************************************************************************
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_title($item = [])
    {
        $data = wp_unslash($_GET);

        if (empty($data['page']) || empty($item['id'])) {
            return $item;
        }

        $page = sanitize_text_field($data['page']);

        //Build row actions
        $actions = array(
            'detail' => __('Detail', 'smtp-mail'),
            'delete' => __('Delete', 'smtp-mail'),
        );

        foreach ($actions as $key => &$action) {
            $query_args = [
                'tab'   => 'list',
                'action' => $key,
                'code'  => $item['id'],
                'token' => wp_create_nonce('delete-nonce')
            ];

            $action = '<a href="' . add_query_arg($query_args, menu_page_url($page, false)) . '">' . $action . '</a>';
        }

        //Return the title contents
        return sprintf(
            '%1$s %2$s',
            /*$1%s*/
            $item['subject'],
            /*$2%s*/
            $this->row_actions($actions)
        );
    }


    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/
            $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/
            $item['id']                //The value of the checkbox should be the record's id
        );
    }


    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns()
    {
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'subject'      => __('Subject', 'smtp-mail'),
            'from_email' => __('From', 'smtp-mail'),
            'to_email'  => __('To', 'smtp-mail'),
            //'to_name' => __('Name', 'smtp-mail'),
            'status'    => __('Status', 'smtp-mail'),
            'message'      => __('Message', 'smtp-mail'),
            'created'      => __('Created', 'smtp-mail'),
            'location'  => __('Location', 'smtp-mail'),
        );

        return $columns;
    }


    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns()
    {
        return [];
    }


    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions()
    {
        $actions = array(
            'delete' => __('Delete', 'smtp-mail'),
        );

        return $actions;
    }


    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action()
    {
        $class = 'notice-success';
        $message = '';
        $action = strtolower(str_replace(' ', '', $this->current_action()));
        $data = wp_unslash($_GET);

        //Detect when a bulk action is being triggered...
        if ('delete' == $action) {
            // token
            $token = isset($data['token']) ? sanitize_text_field($data['token']) : '';
            if ($token == '' || !wp_verify_nonce($token, 'delete-nonce')) {
                $class = 'notice-warning';
                $message = esc_attr__('Delete token not verify!', 'smtp-mail');
            } else if ($this->delete_item() ==  false) {
                $class = 'notice-error';
                $message = esc_attr__('Mail data NULL!', 'smtp-mail');
            } else {
                $message = esc_attr__('Mail data deleted!', 'smtp-mail')
                    . ' ID (' . esc_attr(isset($data['customer']) && is_array($data['customer']) ? implode(',', $data['customer']) : $data['code']) . ')';
            }
        }

        if ($message != '') {
            echo '<div id="message" class="notice ' . esc_attr($class) . ' is-dismissible">'
                , esc_attr($message)
                , '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' 
                , esc_attr__('Dismiss this notice.', 'smtp-mail')
                , '</span></button></div>';
        }
    }

    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items()
    {
        global $wpdb; //This is used only if making any database queries

        $data = wp_unslash($_POST);

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_current_user_screen_meta('per_page', 20);
        if (isset($data['per_page']) && intval($data['per_page']) > 0) {
            $per_page = (int) $data['per_page'];
            $this->update_current_user_screen_meta('per_page', $per_page);
        }

        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $where = '';
        $hidden = array();
        $sortable = $this->get_sortable_columns();


        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);

        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();

        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example 
         * package slightly different than one you might build on your own. In 
         * this example, we'll be using array manipulation to sort and paginate 
         * our data. In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
        //$data = $this->example_data;

        /***********************************************************************
         * ---------------------------------------------------------------------
         * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
         * 
         * In a real-world situation, this is where you would place your query.
         *
         * For information on making queries in WordPress, see this Codex entry:
         * http://codex.wordpress.org/Class_Reference/wpdb
         * 
         * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         * ---------------------------------------------------------------------
         **********************************************************************/
        global $table_prefix;

        $table = $table_prefix . 'smtpmail_data';

        $search = smtpmail_get_var('s'); //If no sort, default to title

        $query_string = ' WHERE id > 0 ';

        if ($search != '') {
            $wild = '%';

            $search = $wild . $wpdb->esc_like($search) . $wild;

            $query_string .= sprintf(" AND ( `subject` LIKE '%s' OR `message` LIKE '%s' ) ", $search, $search);
        }

        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = (int) $this->get_pagenum();

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = (int) $wpdb->get_var($wpdb->prepare('SELECT count(*) FROM %i ', $table) . $query_string);

        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        // $cache_key = sprintf('smtp-mail-data-%s', $current_page);
        // if($search != '') {
        //     $cache_key .= '-'. md5($search);
        // }

        $cache_key = 'smtp-mail-results';

        $results = wp_cache_get($cache_key, 'smtp-mail');

        if (false === $results) {
            $offset = ($current_page - 1) * $per_page;

            $query_string .= $wpdb->prepare('ORDER BY `id` DESC LIMIT %d, %d', $offset, $per_page);

            $results = $wpdb->get_results($wpdb->prepare('SELECT * FROM %i ', $table) . $query_string, ARRAY_A);

            wp_cache_set($cache_key, $results, 'smtp-mail');
        }

        $this->items = $results;

        if (count($this->items) == 0 && smtpmail_options('save_data', 0) == 0) {
            $this->items[] = array(
                'id'            => 1,
                'subject'       => 'Example Data',
                'from_email'    => 'norely@example.com',
                'to_email'      => 'info@example.com',
                'status'        => 1,
                'message'       => 'Message',
                'created'       => current_time('Y-m-d'),
                'params'        => '{"ip":"' . smtpmail_get_server('REMOTE_ADDR') . '"}',
            );
        }

        $total_pages = 0;
        if ($total_items > 0 && $per_page > 0) {
            $total_pages = ceil($total_items / $per_page);
        }

        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args(array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => $total_pages   //WE have to calculate the total number of pages
        ));
    }

    function display_screen_options()
    {
        $columns = $this->get_columns();
        unset($columns['cb']);
        unset($columns['id']);

        $data = wp_unslash($_POST);

        $has_post = count($data) > 0;

        $labels = '';

        $hiddencolumns = $this->get_current_user_screen_meta('hiddencolumns', array());

        $updatehiddencolumns = array();

        foreach ($columns as $name => $title) {
            $checked = 1;

            $key = $name . '-hide';
            if ($has_post && empty($data[$key])) {
                $updatehiddencolumns[$name] = 1;
                $checked = 0;
            } else if (isset($hiddencolumns[$name]) && intval($hiddencolumns[$name]) == 1) {
                $checked = 0;
            }

            $labels .= '<label><input ' . ($checked ? 'checked="checked"' : '') . ' class="hide-column-tog" name="' .
                esc_attr($key) . '" type="checkbox" id="' . esc_attr($name) . '-hide" value="' . esc_attr($name) . '" >' . esc_attr($title) . '</label>';
        }

        if (count($updatehiddencolumns)) {
            $this->update_current_user_screen_meta('hiddencolumns', $updatehiddencolumns);
        }

        $per_page = $this->get_current_user_screen_meta('per_page', 20);

        echo '<div id="screen-meta" class="metabox-prefs smtpmail_screen_meta_options">
		<div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="Screen Options Tab">
			<form id="adv-settings" method="post">
				<fieldset class="metabox-prefs">
					<legend>Columns</legend>' . esc_html($labels) . '
				</fieldset>
				<fieldset class="screen-options">
					<legend>Pagination</legend>
					<label for="subscribers_per_page">Number of items per page:</label>
					<input type="number" step="1" min="1" max="999" class="screen-per-page" name="per_page" id="subscribers_per_page" maxlength="3" value="' . esc_attr($per_page) . '">
				</fieldset>
				<p class="submit"><input type="submit" name="screen-options-apply" id="screen-options-apply" class="button button-primary" value="Apply"></p>
				<input type="hidden" id="screenoptionnonce" name="screenoptionnonce" value="414643bdd4">
			</form>
		</div>
	</div>
	<div id="screen-meta-links">
		<div id="contextual-help-link-wrap" class="hide-if-no-js screen-meta-toggle" style="visibility: hidden;">
			<button type="button" id="contextual-help-link" class="button show-settings" aria-controls="contextual-help-wrap" aria-expanded="false">Help</button>
		</div>
		<div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">
			<button type="button" id="show-settings-link" class="button show-settings screen-meta-active" aria-controls="screen-options-wrap" aria-expanded="true">Screen Options</button>
		</div>
	</div>';
    }

    function delete_item()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'smtpmail_data';

        $data = wp_unslash($_GET);

        $count_deleted = 0;

        if (isset($data['customer']) && is_array($data['customer'])) {
            $ids = array_map('intval', $data['customer']);
            if (count($ids) > 0) {
                foreach($ids as $id) {
                    if($id == 0) continue;

                    if($wpdb->delete($table_name,['id' => $id], ['%d'])) {
                        $count_deleted++;
                    }
                }
            }
        }

        $id = isset($data['code']) ? intval($data['code']) : 0;
        if ($id == 0) return false;

        if($wpdb->delete($table_name,['id' => $id], ['%d'])) {
            $count_deleted++;
        }

        if($count_deleted > 0) {
            wp_cache_delete('smtp-mail-results', 'smtp-mail');
            wp_cache_delete('smtp-mail-result', 'smtp-mail');
        }

        return $count_deleted > 0;
    }

    function get_item($id = 0)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'smtpmail_data';

        $result = wp_cache_get('smtp-mail-result', 'smtp-mail');
        if (false === $result) {
            $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE `id`= %d ", $table_name, $id));

            wp_cache_set('smtp-mail-result', $result, 'smtp-mail');
        }

        return $result;
    }

    function get_current_user_screen_meta($key, $default)
    {
        $current_user = wp_get_current_user();

        $v = (string) get_user_meta($current_user->ID, 'screen_meta_smtpmail_customer_' . $key, $single = true);
        if ($v && $v != '') {
            if (is_array($default)) {
                $v = json_decode($v);
                if (is_object($v)) {
                    $v = get_object_vars($v);
                }
            } else if (is_numeric($default)) {
                $v = (int) $v;
            }
            return $v;
        }

        return $default;
    }

    function update_current_user_screen_meta($key,  $value)
    {
        $current_user = wp_get_current_user();

        if (empty($current_user->ID)) return false;

        return update_user_meta($current_user->ID, 'screen_meta_smtpmail_customer_' . $key, json_encode($value));
    }
}

/**
 * List page
 *
 * @since 1.1.1
 *
 */
function smtpmail_render_customer_list_page()
{
    $data = wp_unslash($_GET);

    $action     = isset($data['action']) ? sanitize_text_field($data['action']) : '';
    $id         = isset($data['code']) ? intval($data['code']) : 0;
    $page       = isset($data['page']) ? sanitize_text_field($data['page']) : '';

    //Create an instance of our package class...
    $SMTPMail_Data_Table = new SMTPMail_Data_List_Table();

    if ($id > 0 && $action == 'detail'):

        $item = $SMTPMail_Data_Table->get_item($id);

        smtpmail_render_customer_detail_form($item);

    else:

        $save_data  = (int) smtpmail_options('save_data');
        
        //Fetch, prepare, sort, and filter our data...
        $SMTPMail_Data_Table->prepare_items();

        add_thickbox();
?>
        <div class="wrap-list-table">
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="customers-filter" method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>" />
                <input type="hidden" name="tab" value="list" />
                <?php wp_nonce_field('delete-nonce', 'token') ?>
                <!-- Now we can render the completed list table -->

                <?php if ($save_data == 0) : ?>
                    <br />
                    <div class="smtpmail-icon-click">
                        <div class="dashicons dashicons-arrow-right-alt"></div>
                    </div>
                    <strong><?php esc_attr_e('You need enable option `Save data SendMail` in General tab.', 'smtp-mail'); ?></strong>
                    <hr />
                    <h3 align=center><?php esc_attr_e('Example Data SendMail', 'smtp-mail'); ?></h3>
                <?php endif; ?>

                <?php $SMTPMail_Data_Table->search_box('Search', 'subject'); ?>
                <?php $SMTPMail_Data_Table->display() ?>
            </form>
        </div>
    <?php
    endif;
}

/**
 * Detail form
 *
 * @since 1.1.2
 *
 * @param object $item 
 */
function smtpmail_render_customer_detail_form($item)
{
    $subject = isset($item->subject) ? __('Reply to ', 'smtp-mail') . $item->subject : '';
    $message = isset($item->message) ? $item->message : '';

    $whois_link = '';

    $params = json_decode($item->params);

    if ($params && is_object($params) && isset($params->ip)) {
        $whois_link = 'http://whois.photoboxone.com/location/' . $params->ip;
    }

    $page = smtpmail_get_var('page');

    ?>
    <form action="<?php echo esc_url(admin_url('options-general.php?page=' . $page . '&tab=detail&code=' . $item->id)); ?>" method="post" class="smtpmail_detail_form">
        <h3><?php esc_attr_e('Guest Message', 'smtp-mail'); ?>:</h3>
        <div class="message-box"><?php echo esc_html(nl2br($message)); ?></div>
        <?php if ($whois_link != ''): ?>
            <h3>
                <a href="<?php echo esc_url($whois_link); ?>" target="_blank" rel="help">
                    <?php esc_attr_e('Where is guest?', 'smtp-mail'); ?>
                </a>
            </h3>
            <div class="smtpmail-icon-click">
                <div class="dashicons dashicons-arrow-right-alt"></div>
            </div>
            <p>
                <a href="<?php echo esc_url($whois_link); ?>" target="_blank" rel="help">
                    <?php esc_attr_e('View location and map!', 'smtp-mail'); ?>
                </a>
            </p>
        <?php endif; ?>
        <h3><?php esc_attr_e('Reply form', 'smtp-mail'); ?></h3>
        <p>
            <label><?php esc_attr_e('Name', 'smtp-mail'); ?>:</label>
            <input name="name" type="text" autocomplete="false" class="inputbox required" />
        </p>
        <p>
            <label><?php esc_attr_e('Email', 'smtp-mail'); ?>:</label>
            <input name="email" type="email" autocomplete="false" class="inputbox required" />
        </p>
        <p>
            <label><?php esc_attr_e('Subject', 'smtp-mail'); ?>:</label>
            <input name="subject" type="text" value="<?php echo esc_attr($subject); ?>" class="inputbox required" />
        </p>
        <p>
            <label><?php esc_attr_e('Message', 'smtp-mail'); ?>:</label>
            <textarea name="message" id="message" rows="8" cols="40" class="textareabox required" autocomplete="false"></textarea>
        </p>
        <p class="buttons">
            <label> </label>
            <input type="submit" name="send_test" id="send_test" class="button button-primary" value="Send">
            <a class="button button-secondary" href="<?php echo esc_url(admin_url('options-general.php?page=' . $page . '&tab=list')); ?>"><?php esc_attr_e('Cancel', 'smtp-mail'); ?></a>
        </p>
    </form>
<?php
}
