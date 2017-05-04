<?php
/**
 * Plugin Name: Gravity Forms Exponent Calculation
 * Plugin URI: https://michaeldozark.com/plugins/gravity-forms-exponents/
 * Description: Adds exponent support for calculations in number fields
 * Version: 0.1.0
 * Author: Michael Dozark
 * Author URI: http://www.michaeldozark.com/
 *
 * @package GravityFormsExponents
 */

if ( ! defined( 'ABSPATH' ) ) exit; // exit if accessed directly

/**
 * per-load plugin setup
 *
 * Call setup function. This should be the only instance of add_action that is not
 * contained within a defined function.
 *
 * @link https://developer.wordpress.org/reference/hooks/plugins_loaded/
 *       Description of `plugins_loaded` action
 * @uses gforms_exponents()
 */
add_action( 'plugins_loaded', 'gforms_exponents', 20 );

/**
 * Set up and configure the plugin
 *
 * @since 0.1.0
 */
function gforms_exponents() {

	/**
	 * Enqueue calculation scripts
	 */
	add_action( 'gform_pre_enqueue_scripts', 'gforms_exponents_wp_enqueue_scripts', 10, 2 );

	/**
	 * Filter backend calculation results
	 *
	 * @link https://www.gravityhelp.com/documentation/article/gform_calculation_result/
	 *       Description of `gform_calculation_result` filter
	 */
	add_filter( 'gform_calculation_result', 'gforms_exponents_calculation', 10, 5 );
}

/**
 * Filter backend calculation results
 *
 * @param float $result   The calculation result
 * @param string $formula The formula after merge tags have been processed
 * @param object $field The calculation field currently being processed
 * @since 0.1.0
 */
function gforms_exponents_calculation( $result, $formula, $field, $form, $entry ) {

	/**
	 * Only evaluate if a caret was used in the formula
	 *
	 * @link http://php.net/manual/en/function.strpos.php
	 *       Description of `strpos` function
	 */
	if ( false !== strpos( $formula, '^' ) ) {

		/**
		 * Check if we are using PHP 5.6 or better
		 *
		 * PHP 5.6 introduced `**` as an exponention operator, which makes the calculation
		 * much more straightforward when it is available.
		 *
		 * @see  http://php.net/manual/en/language.operators.arithmetic.php
		 *       Description of arithmetic operators in PHP
		 * @link http://php.net/manual/en/function.version-compare.php
		 *       Description of `version_compare` function
		 * @link http://php.net/manual/en/function.phpversion.php
		 *       Description of `phpversion` function
		 */
		$php_version = version_compare( phpversion(), '5.6' );

		/**
		 * Sanitize string input
		 *
		 * We are going to strip all characters from the string that do not belong
		 * in our mathematical evaluation. This prevents malicious code from infecting
		 * our site from our `eval` call later in the function
		 *
		 * We are stripping out anything that is not a number, decimal, space,
		 * parentheses, or simple arithmetical operator.
		 *
		 * Note that we are using '@' for delimiters to avoid other common
		 * delimiters ("/","|",etc.) that may be used in our formula or regex
		 * patterns
		 *
		 * @link http://php.net/manual/en/function.preg-replace.php
		 *       Description of `preg_replace` function
		 */
		$formula = preg_replace( '@[^0-9\s\n\r\+\-*\/\^\(\)\.]@is', '', $formula );

		/**
		 * If we are using PHP 5.6+, convert carets to the exponention operator
		 */
		if ( $php_version >= 0 ) {

			/**
			 * Replace carets with exponention operators
			 *
			 * @link http://php.net/manual/en/function.str-replace.php
			 *       Description of `str_replace` function
			 */
			$formula = str_replace( '^', '**', $formula );

		/**
		 * If we are using PHP < 5.6, we'll need to get creative with replacements
		 *
		 * Right now this can handle either numbers or expressions in parentheses for
		 * our exponents, but not nested expressions.
		 *
		 * Example: The following will work just fine
		 * 2^7
		 * 3 ^ 2
		 * ( 1 - 2 ) ^ ( 2 / 3 )
		 *
		 * This will not
		 * ( 1 - ( 2 / 3 ) ) ^ 3
		 *
		 * @todo Can we get nested expressions working? Perhaps with a lookbehind?
		 */
		} else { // if ( $php_version >= 0 )

			/**
			 * Find our exponent expressions
			 *
			 * @link http://php.net/manual/en/function.preg-match-all.php
			 *       Description of the `preg_match_all` function
			 */
			preg_match_all( '@(\([^\(\)]*\)|[\d\.]+)\s*\^\s*(\([^\(\)]*\)|[\d\.]+)@is', $formula, $matches );

			$search = $matches[0];

			$replace = array();

			foreach ( $search as $key => $expression ) {
				$replace[] = pow( eval( "return {$matches[1][$key]};" ), eval( "return {$matches[2][$key]};" ) );
			} // foreach ( $search as $key => $expression )

			$formula = str_replace( $search, $replace, $formula );

		} // if ( $php_version >= 0 )

		/**
		 * Set result equal to evaluated formula
		 *
		 * @link http://php.net/manual/en/function.eval.php
		 *       Description of `eval` function
		 */
		$result = eval( "return {$formula};" );

	} // if ( false !== strpos( $formula, '^' ) )

	return $result;
}

/**
 * Enqueue scripts to show calculations on frontend
 *
 * @param array $form The Form Object
 * @link  https://developer.wordpress.org/reference/functions/wp_enqueue_script/
 *        Description of `wp_enqueue_script` function
 * @since 0.1.0
 */
function gforms_exponents_wp_enqueue_scripts( $form ) {

	/**
	 * Only load if there are calculation fields in the current form
	 */
	if ( GFFormDisplay::has_calculation_field( $form ) ) {

		/**
		 * Load unminified script for debugging purposes
		 *
		 * @see https://codex.wordpress.org/Debugging_in_WordPress
		 */
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );

		/**
		 * Enqueue the script
		 *
		 * @link https://developer.wordpress.org/reference/functions/plugin_dir_url/
		 *       Description of `plugin_dir_url` function
		 */
		wp_enqueue_script( 'gforms-exponents', trailingslashit( plugin_dir_url( __FILE__ ) ) . "gravity-forms-exponents{$min}.js", array( 'gform_gravityforms' ), '0.1.0', true );

	} // if ( GFFormDisplay::has_calculation_field( $form ) )

}