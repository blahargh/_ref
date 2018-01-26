<?php
/**
 * Docer
 */

return '1.2.01';

/***
	Change log:
		v.1.2.03
			(2017-03-01)
			- Improved parser to handle abstract classes.
			- Added $options argument to renderDoc() to "hideProtected", "hidePrivate", and/or "hidePublic".
			
		v.1.2.02
			(2017-02-10)
			- Display the use of tabs in comments.
			
		v.1.2.01
			(2016-07-23)
			- Changed method names to be camelCase instead of starting with a capital letter.
			- Created a namespace so it is now \IMP\Docer.
			
		v.1.1.03
			(2015-12-28)
			- Forms: added a note about using addOption() will change the focus to the
			new sub-element and resetPointer() will need to be used if the focus
			is to be changed back to the main input element.
			
		v.1.1.02
			(2015-09-17)
			- Added code display for better reference.
			
		v.1.1.01
			(2015-08-23)
			- Converted to be compatible with IMP v.1.1.
			
		v.1.0.07
			(2015-06-25)
			- Recognize extended classes.
			
		v.1.0.06
		  (2015-06-04)
			- Fixed to handle formatting spaces such as:
			    @param String $var1 - Description here.
				@param Int    $var2 - Description here.
		
		v.1.0.05
		  (2015-06-02)
			- Added recognition of "const" property declarations.
			- Base URL can be passed in to the RenderList() method to override
			the base URL used to generate the list of links.
			
		v.1.0.04
		  (2015-05-31)
			- Fixed to handle "@param null" and "@return null".
			
		v.1.0.03
			(2015-05-11)
			- Added links to form-theme, data-table, and dataset editor pages.
			
		v.1.0.02
			(2015-03-26)
			- Changed cursor to use CSS "default" when hover over the
			clickable method headers, instead of having it change
			to a "text" cursor.
			- Display version number on pages.
			
		v.1.0.01
			(2015-02-06)
			Functional release.
***/
?>