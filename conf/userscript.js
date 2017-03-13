/* Inspired by: http://www.dokuwiki.org/tips:summary_enforcement */
function installSummaryEnforcement()
{
    var summary_input = document.getElementById('edit__summary');
    if(summary_input !== null)
    {
        var minoredit_input = document.getElementById('minoredit');

        addEvent(summary_input, 'change', enforceSummary);
        addEvent(summary_input, 'keyup', enforceSummary);
        addEvent(minoredit_input, 'change', enforceSummary);
        addEvent(minoredit_input, 'click', enforceSummary);
        enforceSummary(); // summary may be there if we're previewing
    }

    var mysheet=document.styleSheets[0];
	var totalrules=mysheet.cssRules? mysheet.cssRules.length : mysheet.rules.length;
	if (mysheet.insertRule){ //if Standards (Firefox)
		//mysheet.deleteRule(totalrules-1);
		mysheet.insertRule("div.dokuwiki input.button_disabled{color:#999;cursor:default;}", totalrules-1);
	}
	else if (mysheet.addRule){ //else if IE
		//mysheet.removeRule(totalrules-1);
		mysheet.addRule("div.dokuwiki input.button_disabled", "color:#999;cursor:default;");
	}
}

function enforceSummary()
{
    var btn_save = document.getElementById('edbtn__save');
    var summary_input = document.getElementById('edit__summary');
    var minoredit_input = document.getElementById('minoredit');
    var disabled = false;

    if(summary_input.value.replace(/^\s+/,"") === '' && !minoredit_input.checked)
        {disabled = true;}

    if(disabled != btn_save.disabled || btn_save.disabled === null)
    {
        btn_save.className = disabled ? 'button button_disabled' : 'button';
        btn_save.disabled = disabled;
    }
}

addInitEvent(function(){installSummaryEnforcement();});

