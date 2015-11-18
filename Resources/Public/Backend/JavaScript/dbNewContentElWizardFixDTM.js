/**
 * Fix the problem of DTM in dbNewContentElWizard that the last selected
 * tab could not be selected initially (e.g. on page reload) due to
 * his removal via "manipulateWizardItems"
 *
 * @author Tobias Ferger <tobi@tt36.de>
 */

function DTM_activate(idBase,index,doToogle) {
// Hiding all:
	if (DTM_array[idBase]) {
		for(var cnt = 0; cnt < DTM_array[idBase].length ; cnt++) {
			if (DTM_array[idBase][cnt] !== idBase + '-' + index) {
				document.getElementById(DTM_array[idBase][cnt]+'-DIV').style.display = 'none';
// Only Overriding when Tab not disabled
				if (document.getElementById(DTM_array[idBase][cnt]+'-MENU').attributes.getNamedItem('class').nodeValue !== 'disabled') {
					document.getElementById(DTM_array[idBase][cnt]+'-MENU').attributes.getNamedItem('class').nodeValue = 'tab';
				}
			}
		}
	}
// Showing one:
	if (!document.getElementById(idBase+'-'+index+'-MENU')){
		index = 1;
	}
	if (document.getElementById(idBase+'-'+index+'-DIV')) {
		if (doToogle && document.getElementById(idBase+'-'+index+'-DIV').style.display === 'block') {
			document.getElementById(idBase+'-'+index+'-DIV').style.display = 'none';
			if (DTM_origClass === '') {
				document.getElementById(idBase+'-'+index+'-MENU').attributes.getNamedItem('class').nodeValue = 'tab';
			} else {
				DTM_origClass = 'tab';
			}
			top.DTM_currentTabs[idBase] = -1;
		} else {
			document.getElementById(idBase+'-'+index+'-DIV').style.display = 'block';
			if (DTM_origClass === '') {
				document.getElementById(idBase+'-'+index+'-MENU').attributes.getNamedItem('class').nodeValue = 'tabact';
			} else {
				DTM_origClass = 'tabact';
			}
			top.DTM_currentTabs[idBase] = index;
		}
	}
	document.getElementById(idBase+'-'+index+'-MENU').attributes.getNamedItem('class').nodeValue = 'tabact';
}