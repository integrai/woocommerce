<?php

class Integrai_Validator {
  public static function cpf( $cpf ) {
    $cpf = preg_replace( '/[^0-9]/', '', $cpf );

    if ( 11 !== strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
      return false;
    }

    $digit = substr( $cpf, 0, 9 );

    for ( $j = 10; $j <= 11; $j++ ) {
      $sum = 0;

      for ( $i = 0; $i < $j - 1; $i++ ) {
        $sum += ( $j - $i ) * intval( $digit[ $i ] );
      }

      $summod11 = $sum % 11;
      $digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
    }

    return intval( $digit[9] ) === intval( $cpf[9] ) && intval( $digit[10] ) === intval( $cpf[10] );
  }

  public static function cnpj( $cnpj ) {
    $cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );

    if ( 14 !== strlen( $cnpj ) || 0 === intval( substr( $cnpj, -4 ) ) ) {
      return false;
    }

    for ( $t = 11; $t < 13; ) {
      for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c--, ( $p < 9 ) ? $p++ : $p = 2 ) {
        $d += $cnpj[ $c ] * $p;
      }

      if ( intval( $cnpj[ ++$t ] ) !== ( $d = ( ( 10 * $d ) % 11 ) % 10 ) ) {
        return false;
      }
    }

    return true;
  }
}
