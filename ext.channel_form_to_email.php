<?php
/**
 * Channel Form To Email Extension Class
 *
 * @package        channel_form_to_email
 * @author         Udit Rawat <eklavyarwt@gmail.com>
 * @link           http://emerico.in/expression-engine-channel-form-to-email/
 * @copyright      Udit Rawat <eklavyarwt@gmail.com>
 * @license        THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT
 */
class Channel_Form_To_Email_ext
{

    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Extension settings
     *
     * @access      public
     * @var         array
     */
    public $settings = array();

    /**
     * Version (still needed)
     *
     * @access      public
     * @var         string
     */
    public $version;

    // --------------------------------------------------------------------

    /**
     * Name of this package
     *
     * @access      private
     * @var         string
     */
    private $package = 'channel_form_to_email';

    /**
     * This add-on's info based on setup file
     *
     * @access      private
     * @var         object
     */
    private $info;

    /**
     * URI instance
     *
     * @access      private
     * @var         object
     */
    private $uri;

    /**
     * Current site id
     *
     * @access      private
     * @var         int
     */
    private $site_id;

    /**
     * Format category name?
     *
     * @access      private
     * @var         bool
     */
    private $format = TRUE;

    /**
     * Hooks used
     *
     * @access      private
     * @var         array
     */
    private $hooks = array(
        'before_channel_entry_insert',
        'after_channel_entry_insert',
        'before_channel_entry_update',
        'after_channel_entry_update',
        'before_channel_entry_save',
        'after_channel_entry_save',
        'before_channel_entry_delete',
        'after_channel_entry_delete'
    );

    /**
     * Default settings
     *
     * @access      private
     * @var         array
     */
    private $default_settings = array(
        'channel' => 0,
        'recipients' => '',
        'email_cc' => '',
        'email_bcc' => '',
        'email_subject' => 'New Channel Entry',
        'email_type' => 'text',
        'email_template' => '',
        'email_shoot_event' => 'after_channel_entry_insert',
        'date_format' => 'd/m/Y'
    );

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access      public
     * @param       mixed     Array with settings or FALSE
     * @return      null
     */
    function __construct($settings = array())
    {
        // Set the info
        $this->info = ee('App')->get($this->package);

        // And version
        $this->version = $this->info->getVersion();

        // Get site id
        $this->site_id = ee()->config->item('site_id');

        // Set settings
        $this->settings = $this->get_site_settings($settings);
    }

    // --------------------------------------------------------------------

    /**
     * Settings form
     *
     * @access      public
     * @param       array     Current settings
     * @return      string
     */
    public function settings_form($current)
    {
        // --------------------------------------
        // The base URL for this add-on
        // --------------------------------------

        $base_url = ee('CP/URL', 'addons/settings/' . $this->package);

        // --------------------------------------
        // Save when posted
        // --------------------------------------

        if (!empty($_POST)) {
            $this->save_settings($current);

            // Redirect back, so we don't get the send POST vars msg on F5.
            ee()->functions->redirect($base_url);
        }

        // --------------------------------------
        // Get current settings for this site
        // --------------------------------------

        $current = $this->get_site_settings($current);

        // --------------------------------------
        // Create form field for each setting
        // --------------------------------------

        $vars = array(
            'base_url' => $base_url,
            'cp_page_title' => $this->info->getName(),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );


        $channel_array = array();
        $channels_list = ee('Model')->get('Channel')->all();
        $channels_list = $channels_list->sortBy('channel_id');
        foreach ($channels_list as $channel) {
            $channel_array[$channel->channel_id] = $channel->channel_name;
        }

        foreach ($current as $key => $val) {
            $row = array('title' => $key);

            switch ($key) {
                case 'channel':
                    $row['desc'] = 'Select channel for which form is created';
                    if (empty($channel_array)) {
                        $row['fields'] = array($key => array(
                            'type' => 'text',
                            'value' => $val
                        ));
                    } else {
                        $row['fields'] = array($key => array(
                            'type' => 'select',
                            'choices' => $channel_array,
                            'value' => $val
                        ));
                    }
                    break;

                case 'recipients':
                    $row['desc'] = 'Add recipient email address, Add multiple recipient using commas';
                    $row['fields'] = array($key => array(
                        'type' => 'text',
                        'value' => $val
                    ));
                    break;

                case 'email_cc':
                    $row['desc'] = 'Send carbon copy to email';
                    $row['fields'] = array($key => array(
                        'type' => 'text',
                        'value' => $val
                    ));
                    break;

                case 'email_bcc':
                    $row['desc'] = 'Send B carbon copy to email';
                    $row['fields'] = array($key => array(
                        'type' => 'text',
                        'value' => $val
                    ));
                    break;

                case 'email_subject':
                    $row['desc'] = 'Select email subject';
                    $row['fields'] = array($key => array(
                        'type' => 'text',
                        'value' => $val
                    ));
                    break;

                case 'email_type':
                    $row['desc'] = 'Select email template type (text/html)';
                    $row['fields'] = array($key => array(
                        'type' => 'select',
                        'choices' => array(
                                'text' => 'text',
                                'html' => 'html'
                            ),
                        'value' => $val
                    ));
                    break;

                case 'email_template':
                    $row['desc'] = 'Create email template using text, html and predefined text';
                    $row['fields'] = array($key => array(
                        'type' => 'textarea',
                        'value' => $val,
                    ));
                    break;

                case 'email_shoot_event':
                    $row['desc'] = 'When to send email notification';
                    $row['fields'] = array($key => array(
                        'type' => 'select',
                        'choices' => array(
                            'before_channel_entry_insert' => 'Before Entry Insert',
                            'after_channel_entry_insert' => 'After Entry Insert',
                            'before_channel_entry_update' => 'Before Entry Update',
                            'after_channel_entry_update' => 'After Entry Update',
                            'before_channel_entry_save' => 'Before Entry Save',
                            'after_channel_entry_save' => 'After Entry Save',
                            'before_channel_entry_delete' => 'Before Entry Delete',
                            'after_channel_entry_delete' => 'After Entry Delete'
                        ),
                        'value' => $val
                    ));
                    break;

                case 'date_format':
                    $row['desc'] = 'Add php date format as per date() function of php if you are using date function in form';
                    $row['fields'] = array($key => array(
                        'type' => 'text',
                        'value' => $val
                    ));
                    break;

            }

            // There's only one section here
            $vars['sections'][0][] = $row;
        }

        // --------------------------------------
        // Add JS
        // --------------------------------------

        ee()->cp->add_to_foot($this->js());

        // --------------------------------------
        // Load view
        // --------------------------------------

        return ee('View')->make($this->package . ':settings')->render($vars);
    }

    // --------------------------------------------------------------------

    /**
     * Save extension settings
     *
     * @return      void
     */
    private function save_settings($settings)
    {
        // --------------------------------------
        // Loop through default settings, check
        // for POST values, fallback to default
        // --------------------------------------

        foreach ($this->default_settings as $key => $val) {
            $val = ee('Request')->post($key, $val);

            if (is_array($val)) {
                $val = array_values(array_filter($val));
            }

            $settings[$this->site_id][$key] = $val;
        }

        // --------------------------------------
        // Add alert to page
        // --------------------------------------

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('settings_saved'))
            ->addToBody(sprintf(lang('settings_saved_desc'), $this->info->getName()))
            ->defer();

        // --------------------------------------
        // Save serialized settings
        // --------------------------------------

        ee()->db->where('class', __CLASS__);
        ee()->db->update('extensions', array('settings' => serialize($settings)));
    }


    // --------------------------------------------------------------------

    /**
     * Activate extension
     *
     * @access      public
     * @return      null
     */
    public function activate_extension()
    {
        foreach ($this->hooks as $hook) {
            $this->add_hook($hook);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Update Extension
     * @param string $current
     * @return bool
     */
    function update_extension($current = '')
    {
        if ($current == '' OR $current == $this->version) {
            return FALSE;
        }

        if ($current < '1.0') {
            // Update to version 1.0
        }

        ee()->db->where('class', __CLASS__);
        ee()->db->update(
            'extensions',
            array('version' => $this->version)
        );
    }

    // --------------------------------------------------------------------

    /**
     * Disable extension
     *
     * @access      public
     * @return      null
     */
    public function disable_extension()
    {
        // Delete records
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

    // --------------------------------------------------------------------
    // HELPER METHODS
    // --------------------------------------------------------------------

    /**
     * Get settings for this site
     *
     * @access      private
     * @return      mixed
     */
    private function get_site_settings(array $settings)
    {
        // Are there settings for this site?
        $settings = array_key_exists($this->site_id, $settings)
            ? $settings[$this->site_id]
            : array();

        // Always make sure all settings are set
        $settings = array_merge($this->default_settings, $settings);

        // And return it
        return $settings;
    }

    // --------------------------------------------------------------------

    /**
     * Add hook to table
     *
     * @access    private
     * @param    string
     * @return    void
     */
    private function add_hook($hook)
    {
        ee()->db->insert('extensions', array(
            'class' => __CLASS__,
            'method' => $hook,
            'hook' => $hook,
            'settings' => serialize(array($this->site_id => $this->settings)),
            'priority' => 5,
            'version' => $this->info->getVersion(),
            'enabled' => 'y'
        ));
    }

    // --------------------------------------------------------------------

    /**
     * JavaScript for the settings page
     *
     * @access    private
     * @param    string
     * @return    void
     */
    private function js()
    {

    }

    // --------------------------------------------------------------------

    /**
     * Get Related Fields Form Array
     * @param $entry_values
     * @return array
     */
    private function get_related_fields($entry_values)
    {
        $replace_fields = array();
        $attachment_id = 0;
        if (!empty($entry_values)) {
            foreach ($entry_values as $key => $value) {
                if (strpos($key, 'field_id_') === 0) {
                    $field_id = intval(str_replace('field_id_', '', $key));
                    $field = ee('Model')
                        ->get('ChannelField')
                        ->fields('field_id', 'field_list_items', 'field_settings', 'field_name', 'field_type')
                        ->filter('field_id', $field_id)
                        ->filter('site_id', 'IN', $this->site_id)
                        ->first();
                    if ($field->field_type == 'file' && $attachment_id != $field_id) {
                        $attachment_id = $field_id;
                        if(!empty($this->get_attachment_url($entry_values, $field_id))){
                            $replace_fields['attachment'][] = $this->get_attachment_url($entry_values, $field_id);
                        }

                    } else {
                        $value = $this->generate_field_html($field->field_type, $value);
                        $replace_fields[$field_id] = array(
                            'field_id' => $field->field_id,
                            'field_name' => $field->field_name,
                            'field_type' => $field->field_type,
                            'field_value' => $value
                        );
                    }
                }
            }
        }
        return $replace_fields;
    }

    // --------------------------------------------------------------------

    /**
     * Generate html for list fields
     * @param $field_type
     * @param $field_value
     * @return bool|string
     */
    private function generate_field_html($field_type, $field_value)
    {

        if (
            (!empty($field_type) && !empty($field_value) && $field_type == 'checkboxes') ||
            (!empty($field_type) && !empty($field_value) && $field_type == 'multi_select')
        ) {
            $value = explode('|', $field_value);
            $field_value = '<ul>';
            foreach ($value as $key => $val) {
                $field_value .= '<li>' . $val . '</li>';
            }
            $field_value .= '</ul>';
        } elseif (!empty($field_type) && !empty($field_value) && $field_type == 'date') {
            $field_value = date($this->settings['date_format'], $field_value);
        } elseif (!empty($field_type) && $field_type == 'toggle') {
            if ($field_value) {
                $field_value = 'Yes';
            } else {
                $field_value = 'No';
            }
        }

        return $field_value;
    }

    // --------------------------------------------------------------------
    /**
     * Get attachment path
     * @param $entry_values
     * @param $field_id
     * @return mixed|string
     */
    private function get_attachment_url($entry_values, $field_id)
    {
        $attachment_url = '';
        if (!empty($field_id) && !empty($entry_values)) {
            $unformatted_url = $entry_values['field_id_' . $field_id];
            $dir_id = intval($entry_values['field_id_' . $field_id . '_directory']);
            if ($dir_id > 0) {
                $attachment = ee('Model')
                    ->get('UploadDestination')
                    ->fields('url','server_path')
                    ->filter('id', $dir_id)
                    ->filter('site_id', 'IN', $this->site_id)
                    ->first();

                $attachment_url = str_replace('{filedir_' . $dir_id . '}', $attachment->server_path->path, $unformatted_url);
            }
        }
        return $attachment_url;
    }

    // --------------------------------------------------------------------
    // HOOKS
    // --------------------------------------------------------------------
    public function after_channel_entry_insert($entry, $entry_values)
    {
        if (!empty($entry_values) && $this->settings['channel'] == $entry_values['channel_id']) {

            $replace_fields = $this->get_related_fields($entry_values);
            $email_template = $this->settings['email_template'];
            $attachment = array();
            foreach ($replace_fields as $key => $field) {
                if($key == 'attachment'){
                    $attachment = $field;
                } else {
                    $email_template = str_replace('{' . $field['field_name'] . '}', $field['field_value'], $email_template);
                }
            }
            $this->send_email($email_template,$attachment);
        }

    }

    public function before_channel_entry_insert($entry, $entry_values)
    {
        $this->after_channel_entry_insert($entry,$entry_values);
    }

    public function before_channel_entry_update($entry, $entry_values, $entry_modified)
    {
        $this->after_channel_entry_insert($entry,$entry_modified);
    }

    public function after_channel_entry_update($entry, $entry_values, $entry_modified)
    {
        $this->after_channel_entry_insert($entry,$entry_modified);
    }

    public function before_channel_entry_save($entry, $entry_values)
    {
        $this->after_channel_entry_insert($entry,$entry_values);
    }

    public function after_channel_entry_save($entry, $entry_values)
    {
        $this->after_channel_entry_insert($entry,$entry_values);
    }

    public function before_channel_entry_delete($entry, $entry_values)
    {
        $this->after_channel_entry_insert($entry,$entry_values);
    }

    public function after_channel_entry_delete($entry, $entry_values)
    {
        $this->after_channel_entry_insert($entry,$entry_values);
    }

    // --------------------------------------------------------------------
    /**
     * Finally send email
     * @param $email_template
     * @param array $attachments
     */
    private function send_email($email_template, $attachments = array())
    {
        if(!empty(trim($this->settings['recipients']))) {
            ee()->load->library('email');
            ee()->load->helper('text');

            $site_preferences = ee('Model')
                ->get('Site')
                ->filter('site_id', 'IN', $this->site_id)
                ->first();

            $from = $site_preferences->site_system_preferences->webmaster_email;
            $name = $site_preferences->site_system_preferences->webmaster_name;

            ee()->email->wordwrap = true;
            ee()->email->mailtype = $this->settings['email_type'];
            ee()->email->from($from, $name);
            ee()->email->reply_to($from, $name);
            ee()->email->to($this->settings['recipients']);
            if (!empty(trim($this->settings['email_cc']))) {
                ee()->email->cc($this->settings['email_cc']);
            }
            if (!empty(trim($this->settings['email_bcc']))) {
                ee()->email->bcc($this->settings['email_bcc']);
            }

            if(!empty($attachments)){
                foreach($attachments as $attachment){
                    ee()->email->attach($attachment,'inline');
                }
            }

            ee()->email->subject($this->settings['email_subject']);
            ee()->email->message(entities_to_ascii($email_template));
            ee()->email->Send();
        }
    }
}