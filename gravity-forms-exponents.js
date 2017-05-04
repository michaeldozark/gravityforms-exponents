/**
 * Evaluate caret notation on front end
 *
 * Thanks to @Mohsen on Stack Overflow for the caret evaulation function
 *
 * @link    http://stackoverflow.com/questions/15037805/javascript-fixing-that-dan-caret-symbol-for-calculator
 * @version 0.1.0
 * @since   0.1.0
 */

/**
 * Extend the number prototype to have a pow method
 */
Number.prototype.pow = function(n) {return Math.pow(this,n)}

/**
 * Javascript filter
 *
 * @param float  result       The calculation result
 * @param object formulaField The current calculation field object
 * @param int    formId       The ID of the form in use
 * @param object calcObj      The calculation object
 * @link  https://www.gravityhelp.com/documentation/article/gform_calculation_result/
 *        Description of `gform_calculation_result` filter
 */
gform.addFilter( 'gform_calculation_result', function( result, formulaField, formId, calcObj ) {

	/**
	 * Only evaluate if the field has a caret in it
	 *
	 * Technically we should be able to run any formulas through this without
	 * breaking them, but this way we save some small amount of processing
	 *
	 * @link https://www.w3schools.com/jsref/jsref_indexof.asp
	 *       Description of `indexOf` method
	 */
	if ( formulaField.formula.indexOf( '^' ) > -1 || formulaField.formula.indexOf( '**' ) > -1  ) {

		/**
		 * Replace field tags with their associated values
		 *
		 * @param int    formId       The ID of the form in use
		 * @param string formula      The value of the "Formula" field entered in
		 *                            the form admin
		 * @param object formulaField The current calculation field object
		 * @var   string fieldFormula
		 */
		var fieldFormula = calcObj.replaceFieldTags( formId, formulaField.formula, formulaField );

		/**
		 * Sanitize the formula in case we have malicious user inputs. This
		 * prevents malicious code getting passed to our `eval` call later in the
		 * function
		 *
		 * We are stripping out anything that is not a number, decimal, space,
		 * parentheses, or simple arithmetical operator.
		 *
		 * @link https://www.w3schools.com/jsref/jsref_replace.asp
		 *       Description of `replace` method
		 */
		fieldFormula = fieldFormula.replace( /[^0-9\s\n\r\+\-\*\/\^\(\)\.]/g, '' );

		/**
		 * Wrap every number with parentheses and replace the caret symbol with
		 * ".pow"
		 */
		fieldFormula = fieldFormula.replace(/[\d|\d.\d]+/g, function(n){
			return '(' + n + ')'
		}).replace(/\^|\*\*/g, '.pow');

		/**
		 * Set calculation result equal to evaluated string
		 *
		 * @link https://www.w3schools.com/jsref/jsref_eval.asp
		 *       Description of `eval` function
		 */
		result = eval(fieldFormula);

	} // if ( formulaField.formula.indexOf( '^' ) > -1 || formulaField.formula.indexOf( '**' ) > -1  )

	return result;

} );