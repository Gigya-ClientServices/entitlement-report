<?php
  $ssl      = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
  $sp       = strtolower( $_SERVER['SERVER_PROTOCOL'] );
  $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
?>
<?=$protocol?>
