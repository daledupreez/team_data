var team_data = {
	"paging": {
		"pageNum": 0,
		"urlParms": [],
		"pageSize": 0,
		"resultCount": 0
	},
	"time": [ '11:00', '11:30', '12:00', '12:15', '12:30', '12:45', '13:00', '13:15', '13:30', '13:45', '14:00', '14:15', '14:30', '14:45', '15:00', '15:15', '15:30', '16:00' ],
	"matchData": { },
	"currentMatch": null,
	"fn": {},
	"api": {},
	"ui": {},
	"tables": {
		"level": "Levels",
		"member": "Members",
		"team": "Teams",
		"role": "Roles",
		"season": "Seasons",
		"stat": "Stats",
		"venue": "Venues"
	},
	"fields": {
		"control_sequence": [ "date", "venue", "time", "opposition", "level", "our_score", "opposition_score", "is_league", "is_post_season" ],
		"controls": {
			"date": { "header": "Date", "behaviour": "", "type": "input", "attribs": { "_class": "team_data_date", "type": "text", "size": 10 } },
			"venue": { "header": "Venue", "behaviour": "lookup", "type": "input", "attribs": { "_class": "team_data_venue", "type": "text" } },
			"time": { "header": "Time", "behaviour": "time", "type": "input", "attribs": { "_class": "team_data_time", "type": "text", "size": 5 } },
			"opposition": { "header": "Opposition", "behaviour": "lookup", "type": "input", "attribs": { "_class": "team_data_opposition", "type": "text" } },
			"level": { "header": "Level", "behaviour": "select", "type": "select", "attribs": {}, "get_options": "getLevelOptions" },
			"our_score": { "header": "Our Score", "behaviour": "nullable", "type": "input", "attribs": { "type": "text", "size": 5 } },
			"opposition_score": { "header": "Their Score", "behaviour": "nullable", "type": "input", "attribs": { "type": "text", "size": 5 } },
			"is_league": { "header": "League", "behaviour": "checkbox", "type": "input", "attribs": { "type": "checkbox" } },
			"is_postseason": { "header": "Postseason", "behaviour": "checkbox", "type": "input", "attribs": { "type": "checkbox" } }
		}
	},
	"loc": {}
};

for (var tableName in team_data.tables) {
	team_data[tableName] = { "nameIndex": {}, "index": {}, "list": [] };
}

/*
/// Extension to allow class-like inheritance.
/// The approach relies on instantiating the parent object, which adds extra overhead, but things seem to work that way.
/// NOTE: inheritFrom() must be called *BEFORE* any other modifications to a function's prototype because it clobbers existing prototype changes.
Function.prototype.inheritFrom = function inheritFrom(parent)
{
	this.prototype = new parent();
	this.prototype.constructor = this;
	this.prototype._super = new parent();
	this._super = parent;
}
*/

team_data.apiObject = function(refName)
{
	this.refName = refName;
	if (!this.refNameIsValid()) return null;
}

team_data.apiObject.prototype.clearForm = function apiObject_clearForm()
{
	var myForm = this.getForm();
	if (myForm) myForm.reset();
}

team_data.apiObject.prototype.getFields = function apiObject_getFields()
{
	return [ 'id', 'name' ];
}

team_data.apiObject.prototype.getForm = function apiObject_getForm()
{
	if (!this.refNameIsValid()) return null;
	return document.getElementById('team_data_' + this.refName + '_edit');
}

team_data.apiObject.prototype.load = function apiObject_load(id_val)
{
	if (!this.refNameIsValid()) return;
	var postData = { "action": "team_data_get_" + this.refName, "nonce": team_data_ajax.nonce };
	postData[this.refName + '_id'] = id_val;
	var apiObject = this;
	jQuery.post(ajaxurl,postData, function(postResponse) { apiObject.updateForm(postResponse); }); 
}

team_data.apiObject.prototype.loadList = function apiObject_loadList()
{
	if (!this.refNameIsValid()) return;
	var postData = { "action": "team_data_get_all_" + this.refName + "s", "nonce": team_data_ajax.nonce };
	var apiObject = this;
	jQuery.post(ajaxurl,postData, function(postResponse) { apiObject.updateList(postResponse); } );
}

team_data.apiObject.prototype.nameIsRequired = true;

team_data.apiObject.prototype.objectIsValid = function(object_data)
{
	var isValid = true;
	var msg = [];
	if (this.nameIsRequired && ((typeof object_data.name == 'undefined') || (object_data.name == ''))) {
		msg.push(team_data.fn.getLocText("Property '%1' is required",'name'));
		isValid = false;
	}
	if (this.refNameIsValid()) {
		// check that we aren't reusing/overwriting a name
		if ((object_data.name != '') 
			&& team_data[this.refName].nameIndex[object_data.name]
			&& (team_data[this.refName].nameIndex[object_data.name] != object_data.id))
		{
			msg.push(team_data.fn.getLocText("Name '%1' is already in use",object_data.name));
			isValid = false;
		}
	}
	if (!isValid) {
		var alertMsg = team_data.fn.getLocText('Errors saving data:') + '\n' + msg.join('\n');
		alert(alertMsg);
	}
	return isValid;
}

team_data.apiObject.prototype.refNameIsValid = function apiObject_refNameIsValid()
{
	return ((typeof this.refName == 'string') && (this.refName.length > 0) && team_data[this.refName]);
}

team_data.apiObject.prototype.save = function apiObject_save()
{
	if (!this.refNameIsValid()) return;
	var myForm = this.getForm();
	var fields = this.getFields();
	if ((!myForm) || (!fields) || (!fields.length)) return;
	var newData = { "action": "team_data_put_" + this.refName, "nonce": team_data_ajax.nonce };
	for (var i = 0; i < fields.length; i++) {
		var prop = fields[i];
		var control = myForm[this.refName + '_' + prop];
		if (control) newData[prop] = team_data.fn.getControlValue(control);
	}
	if (!this.objectIsValid(newData)) return;
	var apiObject = this;
	jQuery.post(ajaxurl,newData,function(saveResult) { apiObject.saveHandler(saveResult); });
}

team_data.apiObject.prototype.saveHandler = function apiObject_saveHandler(saveResult)
{
	if (!saveResult) return;
	if (saveResult.result == 'error') {
		var msg = team_data.fn.getLocText('Error in save');
		if (saveResult.error_message) msg += '\n' + saveResult.error_message;
		alert(msg);
	}
	else {
		var id_control = document.getElementById('team_data_' + this.refName + '_edit__id');
		if (id_control) id_control.value = saveResult.result;
		this.loadList();
	}
}

team_data.apiObject.prototype.updateForm = function apiObject_updateForm(item_data)
{
	if (!this.refNameIsValid()) return;
	this.clearForm();
	var myForm = this.getForm();
	if ((!myForm) || (!item_data)) return;
	var fields = this.getFields();
	for (var i = 0; i < fields.length; i++) {
		var prop = fields[i];
		if (typeof item_data[prop] != 'undefined') {
			var control = myForm[this.refName + '_' + prop];
			if (control) {
				team_data.fn.setControlValue(control,item_data[prop]);
			}
		}
	}
}

team_data.apiObject.prototype.updateList = function apiObject_updateList(list_data)
{
	if (!list_data) return;
	if (!this.refNameIsValid()) return;
	var index = {};
	var nameIndex = {};
	var list = [];

	for (var i = 0; i < list_data.length; i++) {
		var item = list_data[i];
		if (item && item.id && (item.name != '')) {
			list.push(item.name);
			nameIndex[item.name] = item.id;
			index[item.id] = item.name;
		}
	}
	team_data[this.refName] = { "index": index, "nameIndex": nameIndex, "list": list };
	// update all controls based on new data
	jQuery('.team_data_' + this.refName).autocomplete( { "source": list } );
	var simpleTables = jQuery('.team_data_simple_table_' + this.refName);
	if (simpleTables) {
		for (var i = 0; i < simpleTables.length; i++) {
			var tableDiv = simpleTables[i];
			team_data.ui.renderSimpleTable(tableDiv,this.refName);
		}
	}
}

// API definitions

team_data.api.venue = new team_data.apiObject('venue');
team_data.api.venue.getFields = function venue_getFields()
{
	return [ 'id', 'name', 'is_home', 'info_link', 'directions_link', 'abbreviation' ];
}

team_data.api.level = new team_data.apiObject('level');
team_data.api.level.getFields = function level_getFields()
{
	return [ 'id', 'name', 'abbreviation' ];
}

team_data.api.team = new team_data.apiObject('team');
team_data.api.team.getFields = function team_getFields()
{
	return [ 'id', 'name', 'logo_link', 'abbreviation', 'is_us' ];
}

team_data.api.role = new team_data.apiObject('role');

team_data.api.season = new team_data.apiObject('season');
team_data.api.season.nameIsRequired = false;
team_data.api.season.getFields = function season_getFields()
{
	return [ 'id', 'year', 'season', 'is_current' ];
}

team_data.api.season.repeatLastSeason = function season_repeatLastSeason()
{
	var myForm = this.getForm();
	if (!myForm) return;
	var control = myForm['season_year'];
	if (control) {
		var newYear = team_data.fn.getControlValue(control);
		if (newYear != '') {
			var confirmText = team_data.fn.getLocText("Are you sure you want to repeat the season names for new season '%1'?",newYear);
			var callServer = confirm(confirmText);
			if (callServer) {
				var ajaxData = { "action": "team_data_put_season_repeat", "year": newYear, "nonce": team_data_ajax.nonce };
				var apiObject = this;
				jQuery.post(ajaxurl,ajaxData,function(repeatResult) { apiObject.repeatLastSeasonHandler(repeatResult); });
			}
		}
	}
}

team_data.api.season.repeatLastSeasonHandler = function season_repeatLastSeasonHandler(repeatResult)
{
	if (!repeatResult) return;
	if (repeatResult.result == 'error') {
		var msg = team_data.fn.getLocText('Error in update');
		if (repeatResult.error_message) msg += '\n' + repeatResult.error_message;
		alert(msg);
	}
	else {
		this.loadList();
	}
}

team_data.api.stat = new team_data.apiObject('stat');
team_data.api.stat.getFields = function stat_getFields()
{
	return [ 'id', 'name', 'value_type' ];
}


team_data.api.options = {};
team_data.api.options.getControls = function options_getControls() {
	return {
		"current": document.getElementById('team_data_options_edit__max_matches'),
		"original": document.getElementById('team_data_options_edit__max_matches_orig')
	};
}
team_data.api.options.save = function options_save() {
	var controls = this.getControls();
	if (controls && controls.current && controls.original) {
		var max_matches = parseInt(controls.current.value,10);
		if (max_matches && !isNaN(max_matches) && (max_matches > 0)) {
			if (parseInt(controls.original.value,10) != max_matches) {
				var apiObject = this;
				var setData = { "action": "team_data_set_option", "option_name": "max_matches", "option_value": max_matches, "nonce": team_data_ajax.nonce };
				jQuery.post(ajaxurl,setData,function(saveResult) { apiObject.saveHandler(saveResult); });
			}
		}
	}
}
team_data.api.options.saveHandler = function options_saveHandler(saveResult) {
	if (saveResult && saveResult.set) {
		var controls = this.getControls();
		if (controls && controls.current && controls.original) {
			controls.original.value = controls.current.value;
		}
	}
}


team_data.api.match = {
	"fields": [ 'time', 'level', 'is_league', 'is_postseason', 'our_score', 'opposition_score' ],
	"sharedFields": [ 'date', 'opposition', 'venue', 'season' ]
};
team_data.api.match.allFields = team_data.api.match.sharedFields.concat(team_data.api.match.fields, [ 'id' ]);

team_data.api.match.editMatch = function match_editMatch(match_id)
{
	team_data.api.match.toggleNewMatchDiv(false);
	var postData = { "action": "team_data_get_basic_match", "match_id": match_id, "nonce": team_data_ajax.nonce };
	jQuery.post(ajaxurl,postData,team_data.api.match.editMatchHandler);
}
team_data.api.match.editMatchHandler = function match_editMatchHandler(match_data)
{
	if (!match_data) return;
	var matchForm = document.getElementById('team_data_match_edit');
	if (matchForm) {
		matchForm.reset();
		team_data.api.match.setFieldsFromObject(matchForm,match_data,'match');
		team_data.api.match.toggleEditDiv(true);
	}
}

team_data.api.match.toggleEditDiv = function match_toggleEditDiv(showEdit)
{
	var match_edit_div = document.getElementById('team_data_match_edit_div');
	if (match_edit_div) match_edit_div.style.display = (showEdit ? '' : 'none');
}

team_data.api.match.getScoreDisplayDiv = function match_getScoreDisplayDiv(match_id)
{
	return document.getElementById('team_data_edit__score_display_' + match_id);
}

team_data.api.match.getScoreEditForm = function match_getScoreEditForm(match_id)
{
	return document.getElementById('team_data_edit__score_edit_' + match_id);
}

team_data.api.match.toggleScoreControls = function match_toggleScoreControls(match_id,showEdit)
{
	showEdit = !!showEdit;
	var editForm = team_data.api.match.getScoreEditForm(match_id);
	if (editForm) editForm.style.display = (showEdit ? '' : 'none');
	var saveButton = document.getElementById('team_data_edit_match_score_save_' + match_id);
	if (saveButton) saveButton.style.display = (showEdit ? '' : 'none');
	var editButton = document.getElementById('team_data_edit_match_score_' + match_id);
	if (editButton) editButton.style.display = (showEdit ? 'none' : '');
	var displayDiv = team_data.api.match.getScoreDisplayDiv(match_id);
	if (displayDiv) displayDiv.style.display = (showEdit ? 'none' : '');
	
}

team_data.api.match.editScore = function match_editScore(match_id)
{
	var editForm = team_data.api.match.getScoreEditForm(match_id);
	if (editForm && (editForm.style.display != 'none')) {
		// hide edit div
		team_data.api.match.toggleEditDiv(false);
		var postData = {
			"action": "team_data_update_score",
			"id": match_id,
			"our_score": team_data.fn.getControlValue(editForm.score_our_score),
			"opposition_score": team_data.fn.getControlValue(editForm.score_opposition_score),
			"result": team_data.fn.getControlValue(editForm.score_result),
			"nonce": team_data_ajax.nonce
		};
		jQuery.post(ajaxurl,postData,team_data.api.match.editScoreHandler);
	}
}

team_data.api.match.editScoreHandler = function match_editScoreHandler(saveResult)
{
	if (!saveResult) return;
	if (saveResult.result == 'error') {
		var msg = team_data.fn.getLocText('Error in save');
		if (saveResult.error_message) msg += '\n' + saveResult.error_message;
		alert(msg);
	}
	else {
		var match_id = saveResult.result;
		var editForm = team_data.api.match.getScoreEditForm(match_id);
		var displayDiv = team_data.api.match.getScoreDisplayDiv(match_id);
		if (editForm && displayDiv) {
			var result = team_data.fn.getControlValue(editForm.score_result);
			var our_score = parseInt(team_data.fn.getControlValue(editForm.score_our_score),10);
			var opposition_score = parseInt(team_data.fn.getControlValue(editForm.score_opposition_score),10);
			if ((result == 'W') || (result == 'D') || (result == 'L')) {
				displayDiv.innerHTML = result;
			}
			else if (isNaN(our_score) || isNaN(opposition_score)) {
				displayDiv.innerHTML = '&nbsp;-&nbsp;';
			}
			else {
				var result = 'W';
				if (our_score < opposition_score) {
					result = 'L';
				}
				else if (our_score == opposition_score) {
					result = 'D';
				}
				displayDiv.innerHTML = result + '&nbsp;' + our_score + '&nbsp;-&nbsp;' + opposition_score;
			}
		}
		team_data.api.match.toggleScoreControls(match_id,false);
	}
}

team_data.api.match.fieldIsRequired = function match_fieldIsRequired(fieldName)
{
	var required = true;
	if ((fieldName == 'our_score') || (fieldName == 'opposition_score')) required = false;
	return required;
}

team_data.api.match.getFieldsFromForm = function match_getFieldsFromForm(formObject,fieldList,fieldData,errors,focusList,fieldPrefix)
{
	if ((!formObject) || (!fieldList)) return false;
	if (!errors) errors = [];
	if (!focusList) focusList = [];
	if (!fieldData) fieldData = {};
	if (typeof fieldPrefix == 'undefined') fieldPrefix = 'match';

	for (var i = 0; i < fieldList.length; i++) {
		var fieldName = fieldList[i];
		var control = formObject[ fieldPrefix + '_' + fieldName];
		if (control) {
			var fieldValue = team_data.fn.getControlValue(control);
			if ((typeof fieldValue == 'undefined') || (fieldValue === '') || (fieldValue === null)) {
				if (!team_data.api.match.fieldIsRequired(fieldName)) {
					fieldData[fieldName] = '';
				}
				else {
					errors.push(team_data.fn.getLocText("Property '%1' is required",fieldName));
					focusList.push(control);
				}
			}
			else if (control.nodeName == 'SELECT') {
				fieldData[fieldName + '_id'] = fieldValue;
			}
			else if (team_data[fieldName] && team_data[fieldName].nameIndex) {
				fieldValue = team_data[fieldName].nameIndex[fieldValue];
				if (!fieldValue) {
					errors.push(team_data.fn.getLocText("Please select property '%1' from the drop-down list",fieldValue));
					focusList.push(control);
				}
				else {
					fieldData[fieldName + '_id'] = fieldValue;
				}
			}
			else {
				fieldData[fieldName] = fieldValue;
			}
		}
	}
	return (errors.length > 0);
}

team_data.api.match.setFieldsFromObject = function match_setFieldsFromObject(targetForm,data,namePrefix)
{
	if ((!targetForm) || (!data)) return;
	if (typeof namePrefix == 'undefined') namePrefix = 'match';
	for (var fieldName in data) {
		var displayField = fieldName;
		if (fieldName.indexOf('_id') > 0) displayField = fieldName.substring(0,fieldName.indexOf('_id'));
		var control = targetForm[namePrefix + '_' + displayField];
		if (control) {
			var displayValue = data[fieldName];
			if ((control.nodeName != 'SELECT') && team_data[displayField] && team_data[displayField].index && team_data[displayField].index[displayValue]) {
				displayValue = team_data[displayField].index[displayValue];
			}
			team_data.fn.setControlValue(control,displayValue);
		}
	}
}

team_data.api.match.saveMatch = function match_saveMatch()
{
	var matchForm = document.getElementById('team_data_match_edit');
	if (matchForm) {
		var focusList = [];
		var errors = [];
		var matchData = {};
		team_data.api.match.getFieldsFromForm(matchForm,team_data.api.match.allFields,matchData,errors,focusList,'match');
		if (errors.length > 0) {
			errors.splice(0,0,team_data.fn.getLocText('Errors saving data:'));
			team_data.ui.reportErrors(errors,focusList);
		}
		else {
			matchData.action = 'team_data_update_match';
			matchData.nonce = team_data_ajax.nonce;
			jQuery.post(ajaxurl,matchData,team_data.api.match.saveMatchHandler);
		}
	}
}

team_data.api.match.saveMatchHandler = function match_saveMatchHandler(saveResult)
{
	if (!saveResult) return;
	if (saveResult.result == 'error') {
		var msg = team_data.fn.getLocText('Error in save');
		if (saveResult.error_message) msg += '\n' + saveResult.error_message;
		alert(msg);
	}
	else {
		document.location.reload();
	}
}

team_data.api.match.newMatches = function match_newMatches()
{
	// hide edit div
	team_data.api.match.toggleEditDiv(false);
	var matchCount = parseInt(prompt(team_data.fn.getLocText('How many matches?'),2),10);
	if ((!isNaN(matchCount)) && (matchCount > 0)) {
		team_data.api.match.toggleNewMatchDiv(true);
		var matchCountControl = document.getElementById('team_data_new_match_shared__matchCount');
		if (matchCountControl) matchCountControl.value = matchCount;
		for (var i = 1; i <= matchCount; i++) {
			var matchForm = document.getElementById('team_data_new_match_' + i);
			if (matchForm) matchForm.style.display = '';
		}
	}
}

team_data.api.match.toggleNewMatchDiv = function match_toggleNewMatchDiv(showNewMatch)
{
	var newMatchDiv = document.getElementById('team_data_new_match');
	if (newMatchDiv) {
		newMatchDiv.style.display = (showNewMatch ? '' : 'none');
		var sharedForm = document.getElementById('team_data_new_match_shared');
		if (sharedForm) sharedForm.reset();
		if (!showNewMatch) {
			var matchCountControl = document.getElementById('team_data_new_match_shared__matchCount');
			if (matchCountControl) matchCountControl.value = 0;
		}
		var i = 1;
		var matchForm = document.getElementById('team_data_new_match_' + i);
		while (matchForm) {
			matchForm.reset();
			matchForm.style.display = 'none';
			i++;
			matchForm = document.getElementById('team_data_new_match_' + i);
		}
	}	
}

team_data.api.match.saveNewMatches = function match_saveNewMatches() {
	if (!window.JSON) {
		alert(team_data.fn.getLocText('Your browser does not support JSON.') + '\n' + team_data.fn.getLocText('Please upgrade your browser, or use an alternative browser to perform this action.'));
		return;
	}
	var matchCountControl = document.getElementById('team_data_new_match_shared__matchCount');
	if (matchCountControl) {
		var matchCount = parseInt(matchCountControl.value,10);
		if ((!isNaN(matchCount)) && (matchCount > 0)) {
			var sharedForm = document.getElementById('team_data_new_match_shared');
			var errors = [];
			var focusList = [];
			var matchData = [];
			var sharedValues = {};
			team_data.api.match.getFieldsFromForm(sharedForm,team_data.api.match.sharedFields,sharedValues,errors,focusList,'shared');
			if (errors.length == 0) {
				var fields = team_data.api.match.fields;
				for (var i = 1; i <= matchCount; i++) {
					var currMatch = {};
					for (var fieldName in sharedValues) {
						currMatch[fieldName] = sharedValues[fieldName];
					}
					var currentForm = document.getElementById('team_data_new_match_' + i);
					if (currentForm) {
						team_data.api.match.getFieldsFromForm(currentForm,fields,currMatch,errors,focusList,'match');
					}
					if (errors.length == 0) matchData.push(currMatch);
				}
			}
			if (errors.length > 0) {
				errors.splice(0,0,team_data.fn.getLocText('Errors saving data:'));
				team_data.ui.reportErrors(errors,focusList);
			}
			else {
				var postData = { "action": "team_data_put_new_matches", "match_data": JSON.stringify(matchData), "nonce": team_data_ajax.nonce };
				jQuery.post(ajaxurl,postData,team_data.api.match.saveNewMatchesHandler);
			}
		}
	}
}

team_data.api.match.saveNewMatchesHandler = function match_saveNewMatchesHandler(saveResult) {
	if (!saveResult) return;
	if (saveResult.result == 'error') {
		var msg = team_data.fn.getLocText('Error in save');
		if (saveResult.error_message) msg += '\n' + saveResult.error_message;
		alert(msg);
	}
	else {
		document.location.reload();
	}
}
team_data.api.match.validateMatch = function match_validateMatch(matchObject,checkScore) {
	checkScore = !!checkScore;
	
}
// END API construction

/// UI Operations
team_data.ui.renderSimpleTable = function(parentDiv,tableName)
{
	if ((!team_data[tableName]) || (!team_data.tables[tableName]) || (!team_data[tableName].list)) return
	if (!parentDiv) return;
	while (parentDiv.firstChild) {
		parentDiv.removeChild(parentDiv.firstChild);
	}
	var list = team_data[tableName].list;
	var nameIndex = team_data[tableName].nameIndex;
	var html = [];
	html.push('<div class="team_data_simple_table">');
	html.push('<div class="team_data_simple_header">');
	html.push(team_data.fn.getLocText(team_data.tables[tableName]));
	html.push('</div>');

	var title = team_data.fn.getLocText('Click to view/edit the ' + tableName + ' details');
	var count = 0;
	if (list.length == 0) {
		html.push('<div class="team_data_simple_row team_data_simple_nodata">');
		html.push(team_data.fn.getLocText('No entries'));
		html.push('</div>');
	}
	else {
		for (var i = 0; i < list.length; i++) {
			var entryName = list[i];
			if ((entryName != '') && nameIndex[entryName]) {
				count++;
				html.push('<div class="team_data_simple_row team_data_' + (count%2 ? 'odd' : 'even') + '" onclick="team_data.api.' + tableName + '.load(' + nameIndex[entryName] + ');" title="' + title + '">');
				html.push(team_data.fn.getLocText(entryName));
				html.push('</div>');
			}
		}
	}
	html.push('</div>');
	parentDiv.innerHTML = html.join('');
}

team_data.ui.apiList = [ 'venue', 'level', 'role', 'stat', 'team', 'season' ];

team_data.ui.reportErrors = function(errors,focusList) {
	if (errors && errors.length) {
		var msg = errors.join('\n');
		alert(msg);
		if (focusList && focusList.length) focusList[0].focus();
	}
}

team_data.ui.updateAllData = function()
{
	var apis = team_data.ui.apiList;
	for (var i = 0; i < apis.length; i++) {
		if (team_data.api[apis[i]]) team_data.api[apis[i]].loadList();
	}
}

team_data.ui.enhanceControls = function()
{
	jQuery('.team_data_date').datepicker( { "dateFormat": "yy-mm-dd" } );
	if (team_data.time) {
		jQuery('.team_data_time').autocomplete( { "source": team_data.time } );
	}
}

team_data.fn.getLocText = function(text,arg1,arg2) {
	var locText = (team_data.loc[text] ? team_data.loc[text] : text);
	locText = locText.toString();
	if ((locText.indexOf('%1') > 0) && (typeof arg1 != 'undefined')) locText = locText.replace(/\%1/g,arg1);	
	if ((locText.indexOf('%2') > 0) && (typeof arg2 != 'undefined')) locText = locText.replace(/\%2/g,arg2);
	return locText;
}

team_data.fn.getControlValue = function(control) {
	var controlValue = null;
	if (control) {
		if ((control.nodeName == 'INPUT') && (typeof control.type == 'string')) {
			controlValue = (control.type == 'checkbox' ? (control.checked ? '1' : '0') : control.value);
		}
		else if (control.nodeName == 'SELECT') {
			controlValue = control.value;
		}
	}
	return controlValue;
}

team_data.fn.setControlValue = function(control,value) {
	if (control) {
		if ((control.length > 0) && (typeof control.item == 'function')) {
			var controls = control;
			for (var i = 0; i < controls.length; i++) {
				var currControl = controls.item(i);
				if (currControl && (currControl.nodeName == 'INPUT') && (currControl.type == 'radio') && (currControl.value == value)) {
					currControl.checked = value;
					break;
				}
			}
		}
		else {
			if ((control.nodeName == 'INPUT') && (control.type == 'checkbox')) {
				var isChecked = (value == '1') || (value == true);
				control.checked = isChecked;
			}
			else if ((control.nodeName == 'SELECT') || (control.nodeName == 'INPUT')) {
				control.value = value;
			}
		}
	}
}

team_data.fn.updatePageData = function() {
	var urlParms = document.location.search.toString().substring(1).split('&');
	var parms = [];
	var pageNum = 0;
	for (var i = 0; i < urlParms.length; i++) {
		var pair = urlParms[i].split('=');
		var key = pair.splice(0,1);
		if (key == 'fixturePage') {
			pageNum = parseInt(pair.join('='),10);
		}
		else {
			parms.push(urlParms[i]);
		}
	}
	team_data.paging.pageNum = pageNum;
	team_data.paging.urlParms = parms;
}

team_data.fn.editMatch = function(matchId)
{
	var matchData = this.matchData[matchId];
	if (!matchData) {
		alert(this.getLocText("Match '%1' could not be loaded!",matchId));
	}
	else {
		this.populateFieldsFromObject('edit', matchData);
		this.currentMatch = matchId;
	}
}

team_data.fn.populateFieldsFromObject = function(fieldSuffix, sourceData)
{
	for (var field in team_data.controls) {
		var ctrl = document.getElementById('team_data_' + field + '_' + fieldSuffix);
		if (ctrl) {
			var controlData = team_data.controls[field];
			var type = controlData.behaviour;
			var val = sourceData[field];
			switch(type) {
				case 'lookup':
					var displayVal = this[field].index[val];
					ctrl.value = (displayVal == null ? '' : displayVal);
					break;
				case 'checkbox':
					ctrl.checked = !!val;
					break;
				case 'time':
					val = val.toString().split(' ');
					ctrl.value = (val[0] == 'null' ? '' : val[0]);
					var ampmCtrl = document.getElementById('team_data_' + field + 'Sel_' + fieldSuffix);
					if (ampmCtrl && (val.length > 1)) ampmCtrl.value = val[1].toString().toUpperCase();
					break;
				case 'nullable': // for object=>control, null=>''
				default:
					ctrl.value = (val == null ? '' : val);
					break;
			}
		}
	}
}

team_data.fn.populateObjectFromFields = function(fieldSuffix)
{
	var matchData = {};
	for (var field in team_data.controls) {
		var controlData = team_data.controls[field];
		var behaviour = controlData.behaviour;
		matchData[field] = (behaviour == 'nullable' ? null : '');
		var ctrl = document.getElementById('team_data_' + field + '_' + fieldSuffix);
		if (ctrl) {
			switch(fieldType) {
				case 'nullable': // for control=>object, ''=>null
					matchData[field] = (ctrl.value.toString() == '' ? null : ctrl.value);
					break;
				case 'checkbox':
					matchData[field] = !!ctrl.checked;
					break;
				case 'time':
					var timeVal = ctrl.value;
					var ampmCtrl = document.getElementById('team_data_' + field + 'Sel_' + fieldSuffix);
					if (ampmCtrl.value == 'PM') {
						var pieces = timeVal.split(':');
						var hours = parseInt(pieces[0],10);
						if ((!isNaN(hours)) && (pieces[0] < 12)) pieces[0] = '' + (hours + 12);
						timeVal = timeVal.join(':');
					}
					matchData[field] = timeVal;
					break;
				case 'lookup':
				default:
					matchData[field] = ctrl.value;
					break;
			}
		}
	}
}

team_data.fn.changePage = function(forward,gotoEnd)
{
	forward = !!forward;
	gotoEnd = !!gotoEnd;
	var url = document.location.pathname.toString().split('/').pop();	
	var pageNum = team_data.paging.pageNum;
	if (gotoEnd) {
		pageNum = !forward ? 0 : Math.floor(team_data.paging.resultCount/team_data.paging.pageSize);
	}
	else {
		pageNum = pageNum + (forward ? 1 : -1);
	}
	team_data.paging.urlParms.push('fixturePage='+pageNum);
	document.location = url + '?' + team_data.paging.urlParms.join('&');
}