<?php
/**
 * Name: Latest Properties
 * ID: Latest_Properties_Widget
 * Type: widget
 * Group: WP-Property
 * Class: \UsabilityDynamics\WPP\Latest_Properties_Widget
 * Version: 2.0.0
 * Description: List of the latest properties created on this site
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Latest_Properties_Widget' ) ) {
    /**
     * Latest properties Widget
     */
    class Latest_Properties_Widget extends Widget {

      /**
       * constructor
       */
      function __construct() {
      
        parent::__construct( 
          false, 
          $name = sprintf( __( 'Latest %1s', 'wpp' ), Utility::property_label( 'plural' ) ), 
          array( 
            'description' => sprintf(__( 'List of the latest %1s created on this site', 'wpp' ), Utility::property_label( 'plural' ) ) 
          ) 
        );
      
      }

      /**
       * @see WP_Widget::widget
       */
      function widget( $args, $instance ) {
        global $wp_properties;
        $before_widget = '';
        $before_title = '';
        $after_title = '';
        $after_widget = '';
        extract( $args );

        $title = apply_filters( 'widget_title', $instance[ 'title' ] );
        $instance = apply_filters( 'LatestPropertiesWidget', $instance );
        $stats = $instance[ 'stats' ];
        $image_type = $instance[ 'image_type' ];
        $hide_image = $instance[ 'hide_image' ];
        $show_title = $instance[ 'show_title' ];
        $address_format = $instance[ 'address_format' ];
        $property_stats = $wp_properties[ 'property_stats' ];

        if ( !$image_type ) {
          $image_type = '';
        } else {
          $image_size = \UsabilityDynamics\WPP\Utility::image_sizes( $image_type );
        }

        if ( !isset( $property_stats[ 'property_type' ] ) ) {
          $property_stats[ 'property_type' ] = sprintf(__( '%1s Type', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
        }

        $arg = array(
          'post_type' => 'property',
          'numberposts' => $instance[ 'amount_items' ],
          'post_status' => 'publish',
          'post_parent' => null, // any parent
          'order' => 'DESC',
          'orderby' => 'post_date'
        );

        $postslist = get_posts( $arg );

        echo $before_widget;
        echo "<div class='wpp_latest_properties_widget'>";

        if ( $title ) {
          echo $before_title . $title . $after_title;
        }

        foreach ( $postslist as $post ) {
          $this_property = \UsabilityDynamics\WPP\Utility::get_property( $post->ID, 'return_object=true' );
          $image = wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) );
          $width = ( !empty( $image_size[ 'width' ] ) ? $image_size[ 'width' ] : ( !empty( $image[ 'width' ] ) ? $image[ 'width' ] : '' ) );
          $height = ( !empty( $image_size[ 'height' ] ) ? $image_size[ 'height' ] : ( !empty( $image[ 'height' ] ) ? $image[ 'height' ] : '' ) );
          ?>
          <div class="property_widget_block latest_entry clearfix"
            style="<?php echo( $width ? 'width: ' . ( $width + 5 ) . 'px;' : '' ); ?>">
            <?php if ( $hide_image !== 'on' ) { ?>
              <?php if ( !empty( $image ) ) : ?>
                <a class="sidebar_property_thumbnail latest_property_thumbnail thumbnail"
                  href="<?php echo $this_property->permalink; ?>">
                  <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>"
                    src="<?php echo $image[ 'url' ]; ?>"
                    alt="<?php echo sprintf( __( '%s at %s for %s', 'wpp' ), $this_property->post_title, $this_property->location, $this_property->price ); ?>"/>
                </a>
              <?php else : ?>
                <div class="wpp_no_image" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;"></div>
              <?php endif; ?>
            <?php
            }
            if ( $show_title == 'on' ) {
              echo '<p class="title"><a href="' . $this_property->permalink . '">' . $post->post_title . '</a></p>';
            }

            echo '<ul class="wpp_widget_attribute_list">';
            if ( is_array( $stats ) ) {
              foreach ( $stats as $stat ) {
                $pstat = $stat;
                /** Determine if stat is property_type we switch it to property_type_label */
                if ( $pstat == 'property_type' ) {
                  $pstat = 'property_type_label';
                }
                $content = nl2br( apply_filters( "wpp_stat_filter_{$pstat}", $this_property->$pstat ) );
                if ( empty( $content ) ) {
                  continue;
                }
                echo '<li class="' . $stat . '"><span class="attribute">' . $property_stats[ $stat ] . ':</span> <span class="value">' . $content . '</span></li>';
              }
            }
            echo '</ul>';

            if ( $instance[ 'enable_more' ] == 'on' ) {
              echo '<p class="more"><a href="' . $this_property->permalink . '" class="btn btn-info">' . __( 'More', 'wpp' ) . '</a></p>';
            }
            ?>
          </div>
          <?php
          unset( $this_property );
        }

        if ( $instance[ 'enable_view_all' ] == 'on' ) {
          echo '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', 'wpp' ) . '</a></p>';
        }
        echo '<div class="clear"></div>';
        echo '</div>';
        echo $after_widget;
      }

      /**
       * @see WP_Widget::update
       */
      function update( $new_instance, $old_instance ) {
        return $new_instance;
      }

      /**
       * @see WP_Widget::form
       */
      function form( $instance ) {
        global $wp_properties;

        $title = esc_attr( $instance[ 'title' ] );
        $amount_items = esc_attr( $instance[ 'amount_items' ] );
        $address_format = esc_attr( $instance[ 'address_format' ] );
        $instance_stats = $instance[ 'stats' ];
        $image_type = esc_attr( $instance[ 'image_type' ] );
        $hide_image = $instance[ 'hide_image' ];
        $show_title = $instance[ 'show_title' ];
        $enable_more = $instance[ 'enable_more' ];
        $enable_view_all = $instance[ 'enable_view_all' ];
        $property_stats = $wp_properties[ 'property_stats' ];

        if ( empty( $address_format ) ) {
          $address_format = "[street_number] [street_name],[city], [state]";
        }

        if ( !isset( $property_stats[ 'property_type' ] ) ) {
          $property_stats[ 'property_type' ] = sprintf(__( '%1s Type', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'singular' ) );
        }
        ?>
        <script type="text/javascript">
          //hide and show dropdown whith thumb settings
          jQuery( document ).ready( function ( $ ) {
            jQuery( 'input.check_me' ).change( function () {
              if ( $( this ).attr( 'checked' ) !== true ) {
                jQuery( 'p#choose_thumb' ).css( 'display', 'block' );
              } else {
                jQuery( 'p#choose_thumb' ).css( 'display', 'none' );
              }
            } );
          } );
        </script>
        <p>
          <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
              name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
              value="<?php echo ( !empty( $title ) ) ? $title : sprintf( __( 'Latest %1s', 'wpp' ), \UsabilityDynamics\WPP\Utility::property_label( 'plural' ) ); ?>"/>
          </label>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>">
            <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me"
              name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
              value="on" <?php if ( $hide_image == 'on' ) echo " checked='checked';"; ?> />
            <?php _e( 'Hide Images?', 'wpp' ); ?>
          </label>
        </p>
        <p id="choose_thumb" <?php echo( $hide_image !== 'on' ? 'style="display:block;"' : 'style="display:none;"' ); ?>>
          <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', 'wpp' ); ?>
            <?php \UsabilityDynamics\WPP\Utility::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
          </label>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id( 'per_page' ); ?>"><?php _e( 'Listings to display?', 'wpp' ); ?>
            <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
              name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
              value="<?php echo ( empty( $amount_items ) ) ? 5 : $amount_items; ?>"/>
          </label>
        </p>
        <p><?php _e( 'Select stats you want to display', 'wpp' ) ?></p>
        <p>
          <label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
            <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
              name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
              value="on" <?php if ( $show_title == 'on' ) echo " checked='checked';"; ?> />
            <?php _e( 'Title', 'wpp' ); ?>
          </label>
          <?php foreach ( $property_stats as $stat => $label ): ?>
            <br/>
            <label for="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>">
              <input id="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"
                name="<?php echo $this->get_field_name( 'stats' ); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
              <?php if ( is_array( $instance_stats ) && in_array( $stat, $instance_stats ) ) echo " checked "; ?>">
              <?php echo $label; ?>
            </label>
          <?php endforeach; ?>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id( 'address_format' ); ?>"><?php _e( 'Address Format:', 'wpp' ); ?>
            <textarea style="width: 100%" id="<?php echo $this->get_field_id( 'address_format' ); ?>"
              name="<?php echo $this->get_field_name( 'address_format' ); ?>"><?php echo $address_format; ?></textarea>
          </label>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id( 'enable_more' ); ?>">
            <input id="<?php echo $this->get_field_id( 'enable_more' ); ?>"
              name="<?php echo $this->get_field_name( 'enable_more' ); ?>" type="checkbox"
              value="on" <?php if ( $enable_more == 'on' ) echo " checked='checked';"; ?> />
            <?php _e( 'Show "More" link?', 'wpp' ); ?>
          </label>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>">
            <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
              name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
              value="on" <?php if ( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
            <?php _e( 'Show "View All" link?', 'wpp' ); ?>
          </label>
        </p>
      <?php
      }

    }
    
  }
  
}