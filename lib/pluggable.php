<?php
/**
 * Assorted Pluggable Functions
 *
 * @author potanin@UD
 * @since 2.0.0
 */

if( !function_exists( 'wpp_get_template_part' ) ) {
  /**
   * Get Template Part
   *
   * @param $name
   * @param $path
   * @param $opts
   *
   * @return bool|mixed|string|\UsabilityDynamics\WP_Error|void
   */
  function wpp_get_template_part( $name, $path, $opts ) {
    return \UsabilityDynamics\WPP\Utility::get_template_part( $name, $path, $opts  );
  }
}

/**
 * Implementing this for old versions of PHP
 *
 * @since 1.15.9
 */
if( !function_exists( 'array_fill_keys' ) ) {

  function array_fill_keys( $target, $value = '' ) {

    if( is_array( $target ) ) {

      foreach( $target as $key => $val ) {

        $filledArray[ $val ] = is_array( $value ) ? $value[ $key ] : $value;

      }

    }

    return $filledArray;

  }

}

/**
 * Delete a file or recursively delete a directory
 *
 * @param string  $str Path to file or directory
 * @param boolean $flag If false, doesn't remove root directory
 *
 * @version 0.1
 * @since 1.32.2
 * @author peshkov@UD
 */
if( !function_exists( 'wpp_recursive_unlink' ) ) {
  function wpp_recursive_unlink( $str, $flag = false ) {
    if( is_file( $str ) ) {
      return @unlink( $str );
    } elseif( is_dir( $str ) ) {
      $scan = glob( rtrim( $str, '/' ) . '/*' );
      foreach( $scan as $index => $path ) {
        wpp_recursive_unlink( $path, true );
      }
      if( $flag ) {
        return @rmdir( $str );
      } else {
        return true;
      }
    }
  }
}

