<?php
/**
 * WP-Property Mail Notifications
 * Contains set of static methods for notifications
 *
 * @version 1.0
 * @author Usability Dynamics, Inc. <info@usabilitydynamics.com>
 * @package WP-Property
 * @since 1.38
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Mail' ) ) {

    class Mail {

      /**
       * Wrapper function to send notification with WP-CRM or without one
       *
       * Migrated out of UD_API
       *
       * @param array|mixed $args ['message']          using in email notification
       *
       * @uses self::replace_data()
       * @uses wp_crm_send_notification()
       * @return boolean false if notification was not sent successfully
       * @autor odokienko@UD
       */
      static function send_notification( $args = array() ) {

        $args = wp_parse_args( $args, array(
          'ignore_wp_crm'   => false,
          'user'            => false,
          'trigger_action'  => false,
          'data'            => array(),
          'message'         => '',
          'subject'         => '',
          'crm_log_message' => ''
        ) );

        if( is_numeric( $args[ 'user' ] ) ) {
          $args[ 'user' ] = get_user_by( 'id', $args[ 'user' ] );
        } elseif( filter_var( $args[ 'user' ], FILTER_VALIDATE_EMAIL ) ) {
          $args[ 'user' ] = get_user_by( 'email', $args[ 'user' ] );
        } elseif( is_string( $args[ 'user' ] ) ) {
          $args[ 'user' ] = get_user_by( 'login', $args[ 'user' ] );
        }

        if( !is_object( $args[ 'user' ] ) || empty( $args[ 'user' ]->data->user_email ) ) {
          return false;
        }

        if( function_exists( 'wp_crm_send_notification' ) &&
          empty( $args[ 'ignore_wp_crm' ] )
        ) {

          if( !empty( $args[ 'crm_log_message' ] ) ) {
            wp_crm_add_to_user_log( $args[ 'user' ]->ID, self::replace_data( $args[ 'crm_log_message' ], $args[ 'data' ] ) );
          }

          if( !empty( $args[ 'trigger_action' ] ) && is_callable( array( 'WP_CRM_N', 'get_trigger_action_notification' ) ) ) {
            $notifications = WP_CRM_N::get_trigger_action_notification( $args[ 'trigger_action' ] );
            if( !empty( $notifications ) ) {
              return wp_crm_send_notification( $args[ 'trigger_action' ], $args[ 'data' ] );
            }
          }

        }

        if( empty( $args[ 'message' ] ) ) {
          return false;
        }

        return wp_mail( $args[ 'user' ]->data->user_email, self::replace_data( $args[ 'subject' ], $args[ 'data' ] ), self::replace_data( $args[ 'message' ], $args[ 'data' ] ) );

      }

      /**
       * Send Notification
       *
       * @param type $notification
       *
       * @author peshkov@UD
       * @since 1.38
       */
      function _send_notification( $notification ) {
        $notification = apply_filters( 'wpp::send_notification', $notification );
        \UsabilityDynamics\WPP\Utility::send_notification( $notification );
      }

      /**
       * Notification Template.
       *
       * @author peshkov@UD
       * @since 1.38
       */
      function _notification_template() {
        return apply_filters( 'wpp::mail::template', array(
          'trigger_action'  => 'wpp_default_action',
          'data'            => array(),
          'user'            => false,
          'subject'         => __( 'No Subject', 'wpp' ),
          'message'         => '',
          'crm_log_message' => '',
        ) );
      }

      /**
       *
       * @author peshkov@UD
       * @since 1.38
       */
      function user_created( $user_id, $new_user, $args = array() ) {
        $notification = self::_notification_template();

        $user_data = get_userdata( $user_id );

        $activation_link = get_bloginfo( 'home' ) . '?wpp_user=' . $user_id . '&wpp_user_activation=' . md5( $user_id . $user_data->data->user_email . SECURE_AUTH_SALT );

        $notification[ 'trigger_action' ]  = 'feps_use_account_created';
        $notification[ 'user' ]            = $user_id;
        $notification[ 'subject' ]         = __( 'Account Created', 'wpp' );
        $notification[ 'message' ]         = sprintf( __( 'Hello [display_name]%1$s%1$sYour account on [site_url] has been created and is waiting for activation.%1$s%1$sClick this link to activate your account:%1$s[activation_link]%1$s%1$sAccess data:%1$s[user_login] / [user_password]', 'wpp' ), PHP_EOL );
        $notification[ 'crm_log_message' ] = __( 'New User account created.', 'wpp' );

        $notification[ 'data' ][ 'notification_type' ] = __( 'User Account Created', 'wpp' );
        $notification[ 'data' ][ 'user_email' ]        = $user_data->data->user_email;
        $notification[ 'data' ][ 'display_name' ]      = $user_data->data->display_name;
        $notification[ 'data' ][ 'user_login' ]        = $new_user[ 'user_login' ];
        $notification[ 'data' ][ 'user_password' ]     = $new_user[ 'user_pass' ];
        $notification[ 'data' ][ 'site_url' ]          = site_url();
        $notification[ 'data' ][ 'activation_link' ]   = $activation_link;
        $notification[ 'data' ][ 'system_message' ]    = $notification[ 'message' ];

        $notification = \UsabilityDynamics\WPP\Utility::extend( $notification, $args );

        self::_send_notification( $notification );
      }

      /**
       *
       * @author peshkov@UD
       * @since 1.38
       */
      function feps_post_approved( $post_id, $args = array() ) {
        global $wp_post_statuses;

        $notification = self::_notification_template();

        $_property = \UsabilityDynamics\WPP\Utility::get_property( $post_id, array( 'get_children' => 'false' ) );

        $user_id = $_property[ 'post_author' ];
        $user    = get_user_by( 'id', $user_id );

        $notification[ 'trigger_action' ]  = 'pending_property_approve';
        $notification[ 'user' ]            = $user;
        $notification[ 'subject' ]         = __( 'Submission Approved', 'wpp' );
        $notification[ 'message' ]         = sprintf( __( 'Hello.%1$s%1$sYour %2$s has been published.%1$s%1$sYou can view it using this URL: %1$s[property_link]', 'wpp' ), PHP_EOL, \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
        $notification[ 'crm_log_message' ] = sprintf( __( 'User-submitted %1$s ([property_title]) approved and published.', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );

        $notification[ 'data' ][ 'notification_type' ] = __( 'Submission Approved', 'wpp' );
        $notification[ 'data' ][ 'user_email' ]        = $user->data->user_email;
        $notification[ 'data' ][ 'display_name' ]      = $user->data->display_name;
        $notification[ 'data' ][ 'site_url' ]          = site_url();
        $notification[ 'data' ][ 'url' ]               = $notification[ 'data' ][ 'property_link' ] = class_wpp_feps::get_feps_permalink( $_property, false );
        $notification[ 'data' ][ 'title' ]             = $notification[ 'data' ][ 'property_title' ] = $_property[ 'post_title' ];
        $notification[ 'data' ][ 'status' ]            = @$wp_post_statuses[ get_post_status( $post_id ) ]->label;

        $notification = \UsabilityDynamics\WPP\Utility::extend( $notification, $args );

        self::_send_notification( $notification );
      }

      /**
       * Should be called on FEPS property status update
       *
       * @param int   $post_id
       * @param array $args
       *
       * @author peshkov@UD
       * @since 1.38
       */
      function feps_post_status_updated( $post_id, $args = array() ) {
        global $wp_post_statuses;

        $notification = self::_notification_template();

        $_property = \UsabilityDynamics\WPP\Utility::get_property( $post_id, array( 'get_children' => 'false' ) );

        $user = get_userdata( $_property[ 'post_author' ] );

        $notification[ 'trigger_action' ]  = 'feps_status_updated';
        $notification[ 'user' ]            = $user;
        $notification[ 'subject' ]         = __( 'Status Updated', 'wpp' );
        $notification[ 'message' ]         = sprintf( __( 'Hello.%1$s%1$sYour %2$s status has been updated.%1$s%1$sYou can view it using this URL: %1$s[property_link]', 'wpp' ), PHP_EOL, \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
        $notification[ 'crm_log_message' ] = sprintf( __( 'FEPS %1$s ([property_title]) status has been changed.', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );

        $notification[ 'data' ][ 'notification_type' ] = __( 'Status Updated', 'wpp' );
        $notification[ 'data' ][ 'site_url' ]          = site_url();
        $notification[ 'data' ][ 'user_email' ]        = $user->data->user_email;
        $notification[ 'data' ][ 'display_name' ]      = $user->data->display_name;
        $notification[ 'data' ][ 'user_login' ]        = $user->data->user_login;
        $notification[ 'data' ][ 'user_password' ]     = $user->data->user_pass;
        $notification[ 'data' ][ 'title' ]             = $notification[ 'data' ][ 'property_title' ] = $_property[ 'post_title' ];
        $notification[ 'data' ][ 'url' ]               = $notification[ 'data' ][ 'property_link' ] = class_wpp_feps::get_feps_permalink( $_property, false );
        $notification[ 'data' ][ 'status' ]            = @$wp_post_statuses[ $_property[ 'post_status' ] ]->label;

        $notification = \UsabilityDynamics\WPP\Utility::extend( $notification, $args );

        self::_send_notification( $notification );
      }

      /**
       *
       * @author peshkov@UD
       * @since 1.38
       */
      function feps_post_created( $post_id, $args = array() ) {
        global $wp_post_statuses;

        $notification = self::_notification_template();

        $_property = \UsabilityDynamics\WPP\Utility::get_property( $post_id, array( 'get_children' => 'false' ) );

        $user_id = $_property[ 'post_author' ];
        $user    = get_user_by( 'id', $user_id );

        $notification[ 'trigger_action' ]  = 'pending_property_added';
        $notification[ 'user' ]            = $user;
        $notification[ 'subject' ]         = __( 'Submission Received', 'wpp' );
        $notification[ 'message' ]         = sprintf( __( 'Hello.%1$s%1$sYour %2$s has been received.%1$s%1$sYou can view it using this URL:%1$s[pending_url]', 'wpp' ), PHP_EOL, \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
        $notification[ 'crm_log_message' ] = sprintf( __( 'User submitted %1$s ([property_title]) using FEPS.', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );

        $notification[ 'data' ][ 'notification_type' ] = __( 'Submission Received', 'wpp' );
        $notification[ 'data' ][ 'display_name' ]      = $user->data->display_name;
        $notification[ 'data' ][ 'user_email' ]        = $user->data->user_email;
        $notification[ 'data' ][ 'site_url' ]          = site_url();
        $notification[ 'data' ][ 'pending_url' ]       = class_wpp_feps::get_feps_permalink( $_property, false );
        $notification[ 'data' ][ 'title' ]             = $notification[ 'data' ][ 'property_title' ] = $_property[ 'post_title' ];
        $notification[ 'data' ][ 'status' ]            = @$wp_post_statuses[ $_property[ 'post_status' ] ]->label;

        $notification = \UsabilityDynamics\WPP\Utility::extend( $notification, $args );

        self::_send_notification( $notification );
      }

    }
  }
}



