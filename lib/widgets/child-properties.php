<?php
/**
 * Name: Child Properties
 * ID: Child_Properties_Widget
 * Type: widget
 * Group: WP-Property
 * Class: \UsabilityDynamics\WPP\Child_Properties_Widget
 * Version: 2.0.0
 * Description: Show child properties (if any) for currently displayed property
 */
namespace UsabilityDynamics\WPP {

  if( !class_exists( 'UsabilityDynamics\WPP\Child_Properties_Widget' ) ) {
    /**
     * Child Properties Widget
     *
     */
    class Child_Properties_Widget extends Widget {
      /**
       * Constructor
       */
      function __construct() {
        
        parent::__construct( 
          false, 
          $name = sprintf( __( 'Child %1s', 'wpp' ), Utility::property_label( 'plural' ) ), 
          array( 
            'description' => sprintf( __( 'Show child %1s (if any) for currently displayed %2s', 'wpp' ), Utility::property_label( 'plural' ), Utility::property_label( 'singular' ) ) 
          ) 
        );
        
      }

      /**
       * @see WP_Widget::widget
       */
      function widget( $args, $instance ) {
        global $post, $wp_properties;
        $before_widget = '';
        $before_title  = '';
        $after_title   = '';
        $after_widget  = '';
        extract( $args );

        $title          = apply_filters( 'widget_title', $instance[ 'title' ] );
        $instance       = apply_filters( 'ChildPropertiesWidget', $instance );
        $show_title     = $instance[ 'show_title' ];
        $image_type     = $instance[ 'image_type' ];
        $hide_image     = $instance[ 'hide_image' ];
        $stats          = $instance[ 'stats' ];
        $address_format = $instance[ 'address_format' ];
        $amount_items   = $instance[ 'amount_items' ];

        if( !isset( $post->ID ) ) {
          return;
        }

        if( !$image_type ) {
          $image_type = '';
        } else {
          $image_size = \UsabilityDynamics\WPP\Utility::image_sizes( $image_type );
        }

        $argus = array(
          'post_type'   => 'property',
          'numberposts' => $amount_items,
          'post_status' => 'publish',
          'post_parent' => $post->ID,
        );

        $attachments = get_posts( $argus );

        // Bail out if no children
        if( count( $attachments ) < 1 ) {
          return;
        }

        echo $before_widget;
        echo "<div class='wpp_child_properties_widget'>";

        if( $title ) {
          echo $before_title . $title . $after_title;
        }

        foreach( $attachments as $attached ) {
          $this_property = \UsabilityDynamics\WPP\Utility::get_property( $attached->ID, 'return_object=true' );
          $image         = wpp_get_image_link( $this_property->featured_image, $image_type, array( 'return' => 'array' ) );
          $width         = ( !empty( $image_size[ 'width' ] ) ? $image_size[ 'width' ] : ( !empty( $image[ 'width' ] ) ? $image[ 'width' ] : '' ) );
          $height        = ( !empty( $image_size[ 'height' ] ) ? $image_size[ 'height' ] : ( !empty( $image[ 'height' ] ) ? $image[ 'height' ] : '' ) );
          ?>
          <div class="property_widget_block apartment_entry clearfix"
            style="<?php echo( $width ? 'width: ' . ( $width + 5 ) . 'px;' : '' ); ?>">
            <?php if( $hide_image !== 'on' ) : ?>
              <?php if( !empty( $image ) ): ?>
                <a class="sidebar_property_thumbnail thumbnail" href="<?php echo $this_property->permalink; ?>">
                  <img width="<?php echo $image[ 'width' ]; ?>" height="<?php echo $image[ 'height' ]; ?>"
                    src="<?php echo $image[ 'link' ]; ?>"
                    alt="<?php echo sprintf( __( '%s at %s for %s', 'wpp' ), $this_property->post_title, $this_property->location, $this_property->price ); ?>"/>
                </a>
              <?php else: ?>
                <div class="wpp_no_image" style="width:<?php echo $width; ?>px;height:<?php echo $height; ?>px;"></div>
              <?php endif; ?>
            <?php endif; ?>
            <?php if( $show_title == 'on' ): ?>
              <p class="title"><a
                  href="<?php echo $this_property->permalink; ?>"><?php echo $this_property->post_title; ?></a></p>
            <?php endif; ?>
            <ul class="wpp_widget_attribute_list">
              <?php if( is_array( $stats ) ): ?>
                <?php foreach( $stats as $stat ): ?>
                  <?php $content = nl2br( apply_filters( "wpp_stat_filter_{$stat}", $this_property->$stat ) ); ?>
                  <?php if( empty( $content ) ) continue; ?>
                  <li class="<?php echo $stat ?>"><span
                      class='attribute'><?php echo $wp_properties[ 'property_stats' ][ $stat ]; ?>:</span> <span
                      class='value'><?php echo $content; ?></span></li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>

            <?php if( $instance[ 'enable_more' ] == 'on' ) : ?>
              <p class="more"><a href="<?php echo $this_property->permalink; ?>"
                  class="btn btn-info"><?php _e( 'More', 'wpp' ); ?></a></p>
            <?php endif; ?>
          </div>
          <?php
          unset( $this_property );
        }

        if( $instance[ 'enable_view_all' ] == 'on' ) {
          echo '<p class="view-all"><a href="' . site_url() . '/' . $wp_properties[ 'configuration' ][ 'base_slug' ] . '" class="btn btn-large">' . __( 'View All', 'wpp' ) . '</a></p>';
        }
        echo '<div class="clear"></div>';
        echo '</div>';
        echo $after_widget;
      }

      /** @see WP_Widget::update */
      function update( $new_instance, $old_instance ) {
        return $new_instance;
      }

      /** @see WP_Widget::form */
      function form( $instance ) {
        global $wp_properties;
        $title           = esc_attr( $instance[ 'title' ] );
        $show_title      = $instance[ 'show_title' ];
        $address_format  = esc_attr( $instance[ 'address_format' ] );
        $image_type      = esc_attr( $instance[ 'image_type' ] );
        $amount_items    = esc_attr( $instance[ 'amount_items' ] );
        $property_stats  = $instance[ 'stats' ];
        $hide_image      = $instance[ 'hide_image' ];
        $enable_more     = $instance[ 'enable_more' ];
        $enable_view_all = $instance[ 'enable_view_all' ];

        if( empty( $address_format ) )
          $address_format = "[street_number] [street_name], [city], [state]";

        ?>
        <script type="text/javascript">
          //hide and show dropdown whith thumb settings
          jQuery( document ).ready( function( $ ) {
            $( 'input.check_me_child' ).change( function() {
              if( $( this ).attr( 'checked' ) !== true ) {
                $( 'p#choose_thumb_child' ).css( 'display', 'block' );
              } else {
                $( 'p#choose_thumb_child' ).css( 'display', 'none' );
              }
            } )
          } );
        </script>
        <p><?php _e( 'The widget will not be displayed if the currently viewed property has no children.', 'wpp' ); ?></p>
        <p>
          <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'wpp' ); ?>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
              name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
          </label>
        </p>


        <p>
          <label for="<?php echo $this->get_field_id( 'hide_image' ); ?>">
            <input id="<?php echo $this->get_field_id( 'hide_image' ); ?>" class="check_me_child"
              name="<?php echo $this->get_field_name( 'hide_image' ); ?>" type="checkbox"
              value="on" <?php if( $hide_image == 'on' ) echo " checked='checked';"; ?> />
            <?php _e( 'Hide Images?', 'wpp' ); ?>
          </label>
        </p>
        <p id="choose_thumb_child" <?php
        if( $hide_image !== 'on' )
          echo 'style="display:block;"';
        else
          echo 'style="display:none;"';
        ?>>
          <label for="<?php echo $this->get_field_id( 'image_type' ); ?>"><?php _e( 'Image Size:', 'wpp' ); ?>
            <?php \UsabilityDynamics\WPP\Utility::image_sizes_dropdown( "name=" . $this->get_field_name( 'image_type' ) . "&selected=" . $image_type ); ?>
          </label>

        <p>
          <label for="<?php echo $this->get_field_id( 'amount_items' ); ?>"><?php _e( 'Listings to display?', 'wpp' ); ?>
            <input style="width:30px" id="<?php echo $this->get_field_id( 'amount_items' ); ?>"
              name="<?php echo $this->get_field_name( 'amount_items' ); ?>" type="text"
              value="<?php echo ( empty( $amount_items ) ) ? 5 : $amount_items; ?>"/>
          </label>
        </p>

        <p><?php _e( 'Select the stats you want to display', 'wpp' ); ?></p>
        <p>
          <label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
            <input id="<?php echo $this->get_field_id( 'show_title' ); ?>"
              name="<?php echo $this->get_field_name( 'show_title' ); ?>" type="checkbox"
              value="on" <?php if( $show_title == 'on' ) echo " checked='checked';"; ?> />
            <?php _e( 'Title', 'wpp' ); ?>
          </label>
        </p>
        <?php foreach( $wp_properties[ 'property_stats' ] as $stat => $label ): ?>
          <label for="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>">
            <input id="<?php echo $this->get_field_id( 'stats' ); ?>_<?php echo $stat; ?>"
              name="<?php echo $this->get_field_name( 'stats' ); ?>[]" type="checkbox" value="<?php echo $stat; ?>"
              <?php if( is_array( $property_stats ) && in_array( $stat, $property_stats ) ) echo " checked "; ?> />

            <?php echo $label; ?>
          </label><br/>
        <?php endforeach; ?>


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
              value="on" <?php if( $enable_more == 'on' ) echo " checked='checked';"; ?> />
            <?php _e( 'Show "More" link?', 'wpp' ); ?>
          </label>
        </p>

        <p>
          <label for="<?php echo $this->get_field_id( 'enable_view_all' ); ?>">
            <input id="<?php echo $this->get_field_id( 'enable_view_all' ); ?>"
              name="<?php echo $this->get_field_name( 'enable_view_all' ); ?>" type="checkbox"
              value="on" <?php if( $enable_view_all == 'on' ) echo " checked='checked';"; ?> />
            <?php _e( 'Show "View All" link?', 'wpp' ); ?>
          </label>
        </p>



      <?php
      }

    }
    
  }
  
}
