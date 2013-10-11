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
	"lookups": {
		"opposition": "team"
	},
	"sourceMap": {
		"opposition": "team"
	},
	"tables": {
		"level": "Levels",
		"member": "Members",
		"team": "Teams",
		"list": "Email Lists",
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
			"opposition": { "header": "Opposition", "behaviour": "lookup", "type": "input", "attribs": { "_class": "team_data_team", "type": "text" } },
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
	return [ 'id', 'name', 'logo_link', 'abbreviation', 'is_us', 'info_link' ];
}

team_data.api.list = new team_data.apiObject('list');
team_data.api.list.getFields = function list_getFields()
{
	return [ 'id', 'name', 'comment', 'auto_enroll', 'display_name', 'admin_only', 'from_email', 'from_name' ];
}

team_data.api.email = {};
team_data.api.email.getFields = function email_getFields()
{
	return [ 'subject', 'message', 'list_id', 'replyto' ];
}
team_data.api.email.sendEmail = function email_sendEmail()
{
	var formPrefix = 'team_data_send_email_';
	var submitData = { "action": "team_data_send_email", "nonce": team_data_ajax.nonce };
	var fields = this.getFields();
	for (var i = 0; i < fields.length; i++) {
		var field = fields[i];
		var control = document.getElementById(formPrefix + field);
		if (control) {
			submitData[field] = team_data.fn.getControlValue(control);
		}
	}
	jQuery.post(ajaxurl, submitData, function(sendResult) { alert(JSON.stringify(sendResult)); });
}



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


team_data.api.options = {
	"fields": [ "max_matches", "email_enabled", "allow_all_member_mail", "html_template", "text_footer", "email_from", "email_from_name", "email_prefix", "email_summary_to", "use_smtp", "smtp_server", "smtp_port", "smtp_conn_sec", "smtp_user", "smtp_password" ]
};
team_data.api.options.getControls = function options_getControls(fieldName) {
	if (this.fields.indexOf(fieldName) == -1) return null;
	return {
		"current": document.getElementById('team_data_options_edit__' + fieldName),
		"original": document.getElementById('team_data_options_edit__' + fieldName + '_orig')
	};
}
team_data.api.options.save = function options_save() {
	for (var i = 0; i < this.fields.length; i++) {
		var field = this.fields[i];
		var controls = this.getControls(field);
		if (controls && controls.current) {
			var doUpdate = false;
			if (controls.original) {
				var value = team_data.fn.getControlValue(controls.current);
				var orig_value = team_data.fn.getControlValue(controls.original);
				if (controls.current.type == 'number') {
					value = parseInt(value,10);
					orig_value = parseInt(orig_value,10);
					if (isNaN(value) || (value <= 0)) {
						continue;
					}
				}
				if (value != orig_value) {
					doUpdate = true;
				}
			}
			else if (field == 'user_password') {
				var value = controls.current.value;
				if (value != '') {
					if (value == -1) value = '';
					doUpdate = true;
				}
			}
			if (doUpdate) {
				var apiObject = this;
				var setData = { "action": "team_data_set_option", "option_name": field, "option_value": value, "nonce": team_data_ajax.nonce };
				jQuery.post(ajaxurl,setData,function(saveResult) { apiObject.saveHandler(saveResult); });
			}
		}
	}
}
team_data.api.options.saveHandler = function options_saveHandler(saveResult) {
	if (saveResult && saveResult.set && saveResult.option) {
		var controls = this.getControls(saveResult.option);
		if (controls && controls.current && controls.original) {
			controls.original.value = controls.current.value;
			if (saveResult.option == 'smtp_password') {
				controls.original.value = '';
				controls.current.value = '';
			}
		}
		if (saveResult.option == 'email_enabled') document.location.reload();
	}
}

team_data.api.member_search = {
	"propList": [ 'first_name', 'nick_name', 'last_name', 'email', 'active', 'cell' ],
	"searchFields": [ 'first_name', 'last_name', 'email', 'active' ]
};

team_data.api.member_search.buildIndex = function member_search_buildIndex()
{
	if ((!team_data.member_data) || (!team_data.member_data.members)) return;
	var index = {};
	var members = team_data.member_data.members;
	for (var i = 0; i < members.length; i++) {
		var member = members[i];
		if (member && member.id) index[member.id] = i;
	}
	team_data.member_data.index = index;
}

team_data.api.member_search.clear = function member_search_clear()
{
	var form = document.getElementById('team_data_member_search');
	if (form) form.reset();
	this.render(team_data.member_data.members);
}

team_data.api.member_search.editMember = function member_search_editMember(memberPos)
{
	memberPos = parseInt(memberPos,10);
	if ((!team_data.member_data.currentList) || (!team_data.member_data.currentList[memberPos])) return;
	var currMember = team_data.member_data.currentList[memberPos];
	var row = document.getElementById('member_search_result_' + currMember.id);
	if (!row) return;
	row.innerHTML = this.getRowContents(currMember,memberPos,true);
}

team_data.api.member_search.getAllMembers = function member_search_getAllMembers()
{
	var postData = { "action": "team_data_get_all_member_data", "nonce": team_data_ajax.nonce };
	var apiObject = this;
	jQuery.post(ajaxurl,postData, function(postResponse) { apiObject.updateAllMembers(postResponse); } );
}
team_data.api.member_search.getForm = function member_search_getForm()
{
	return document.getElementById('team_data_member_search');
}
team_data.api.member_search.getRowContents = function member_search_getRowContents(member,pos,editable)
{
	var html = [];
	var haveLists = (team_data.list.list && (team_data.list.list.length > 0));
	var listIndex = (haveLists ? team_data.list.index : {});
	props = this.propList;
	if (editable) {
		html.push('<td>');
		html.push('<input id="member_search_result_' + pos + '__update" type="button" class="team_data_button" onclick="team_data.api.member_search.updateMember(\'' + member.id + '\');" value="' + team_data.fn.getLocText('Save') + '"/>');
		html.push('<input id="member_search_result_' + pos + '__cancel" type="button" class="team_data_button" onclick="team_data.api.member_search.render();" value="' + team_data.fn.getLocText('Discard') + '"/>')
		html.push('</td>');
	}
	else {
		html.push('<td><input id="member_search_result_' + member.id + '__edit" type="button" class="team_data_button" onclick="team_data.api.member_search.editMember(\'' + pos + '\');" value="' + team_data.fn.getLocText('Edit') + '"/></td>');
	}
	for (var j = 0; j < props.length; j++) {
		var prop = props[j];
		var val = (!member[prop]) ? '' : member[prop];
		if (editable) {
			var inputType = 'text';
			var valAttrib = 'value="' + val + '"';
			switch (prop) {
				case 'email':
					inputType = 'email';
					break;
				case 'active':
					inputType = 'checkbox';
					valAttrib = 'checked="1"';
					break;
				case 'cell':
					inputType = 'tel';
					break;
			};
			val = '<input id="member_search_edit_' + member.id + '__' + prop + '" type="' + inputType + '" name="' + prop + '" ' + valAttrib + ' />';
		}
		else {
			val = (val === '') ? '&nbsp;' : val;
			if (prop == 'active') val = team_data.fn.getLocText( ( val == '1' ? 'Yes' : 'No') );
		}
		html.push('<td>' + val + '</td>');
	}
	if ((!member.lists) || (!member.lists.length) || (!haveLists)) {
		html.push('<td>&nbsp;</td>');
	}
	else {
		if (editable) {
			html.push('<td id="member_search_edit_' + member.id + '__listTD">');
			for (var listID in team_data.list.index) {
				var listHTML = team_data.fn.escapeHTML(team_data.list.index[listID]);
				var cellID = 'member_search_edit_' + member.id + '_list_' + listID;
				var checked = (member.lists.indexOf(listID) > -1 ? 'checked="1"' : '');
				html.push('<span nowrap="1">');
				html.push('<input type="checkbox" id="' + cellID + '" name="list" listid="' + listID + '" title="' + listHTML + '" value="' + listID + '" ' + checked + ' class="team_data_checkbox" />');
				html.push('<label for="' + cellID + '" class="team_data_checkbox_label">' + listHTML + '</label>');
				html.push('</span>');
			}
		}
		else {
			var listNames = [];
			for (var k = 0; k < member.lists.length; k++) {
				var list_id = member.lists[k];
				if (listIndex[list_id]) listNames.push(listIndex[list_id]);
			}
			listNames = listNames.join(', ');
			listNames = (listNames == '' ? '&nbsp;' : listNames);
			html.push('<td>' + listNames + '</td>');
		}
	}
	return html.join('');
}

team_data.api.member_search.render = function member_search_render(members)
{
	if ((!members) && team_data.member_data.currentList) members = team_data.member_data.currentList;
	if ((!members) && team_data.member_data) members = team_data.member_data.members;
	if (!team_data.member_data.index) this.buildIndex();
	team_data.member_data.currentList = members;
	var div = document.getElementById('team_data_members');
	if ((!members) || (members.length == 0)) {
		div.innerHTML = '<div class="team_data_member no_results">' + team_data.fn.getLocText('No results') + '</div>';
		return;
	}
	var html = [];
	html.push('<table class="team_data_table team_data_members">');
	html.push('<tr class="team_data_members_header">');
	html.push('<th>&nbsp;</th>');
	html.push('<th>' + team_data.fn.getLocText('First Name') + '</th>');
	html.push('<th>' + team_data.fn.getLocText('Nickname') + '</th>');
	html.push('<th>' + team_data.fn.getLocText('Last Name') + '</th>');
	html.push('<th>' + team_data.fn.getLocText('Email') + '</th>');
	html.push('<th>' + team_data.fn.getLocText('Active') + '</th>');
	html.push('<th>' + team_data.fn.getLocText('Cell') + '</th>');
	html.push('<th>' + team_data.fn.getLocText('Email Lists') + '</th>');
	html.push('</tr>');

	var count = 0;
	for (var i = members.length - 1; i >= 0; i--) {
		var member = members[i];
		html.push('<tr id="member_search_result_' + member.id + '" resultpos="' + i + '">');
		html.push(this.getRowContents(member,i,false));
		html.push('</tr>');
		count++;
		if (count == 50) {
			html.push('<tr class="team_data_more_data">');
			html.push('<td colspan="' + props.length + '">');
			html.push(team_data.fn.getLocText('More members exist.') + 'nbsp;' + team_data.fn.getLocText('Use search criteria to narrow down results.'));
			html.push('</td></tr>');
			break;
		}
	}
	html.push('</table>');

	div.innerHTML = html.join('');
}

team_data.api.member_search.search = function member_search_search()
{
	if ((!team_data.member_data) || (!team_data.member_data.members)) return;
	var form = this.getForm();
	if (!form) return;
	var fields = this.searchFields;
	var criteria = [];
	for (var i = 0; i < fields.length; i++) {
		var ctrl = form['team_data_member_search__' + fields[i]];
		var val = '';
		if (ctrl) val = team_data.fn.getControlValue(ctrl);
		if (val !== '') criteria.push( { "property": fields[i], "value": String(val).toLowerCase() } );
	}
	var listControls = form.member_search_lists;
	if (listControls && listControls.length) {
		for (var i = 0; i < listControls.length; i++) {
			var ctrl = listControls.item(i);
			if (ctrl && ctrl.checked) {
				criteria.push( { "property": "list", "value": String(ctrl.value) } );
			}
		}
	}
	if (!criteria.length) {
		this.render(team_data.member_data.members);
		return;
	}
	var results = [];
	var member_data = team_data.member_data.members;
	for (var i = 0; i < member_data.length; i++) {
		var match = true;
		var member = member_data[i];
		for (var j = 0; j < criteria.length; j++) {
			var criterion = criteria[j];
			if (criterion.property == 'list') {
				if (member.lists.indexOf(criterion.value) == -1) {
					match = false;
					break;
				}
			}
			else {
				var compString = String(member[criterion.property]).substring(0,criterion.value.length).toLowerCase();
				if (compString != criterion.value) {
					match = false;
					break;
				}
			}
		}
		if (match) results.push(member);
	}
	this.render(results);
}
team_data.api.member_search.updateAllMembers = function member_search_updateAllMembers(member_data)
{
	team_data.member_data = {
		"members": member_data
	}
	this.render();
}

team_data.api.member_search.updateMember = function member_search_updateMember(memberID)
{
	memberID = parseInt(memberID,10);
	if (isNaN(memberID) || !memberID) return;
	var postData = { "action": "team_data_put_member_simple", "id": memberID, "nonce": team_data_ajax.nonce };
	var props = this.propList;
	for (var i = 0; i < props.length; i++) {
		var prop = props[i];
		var ctrl = document.getElementById('member_search_edit_' + memberID + '__' + prop);
		var val = (ctrl ? team_data.fn.getControlValue(ctrl) : '');
		postData[prop] = val;
	}
	var listCell = document.getElementById('member_search_edit_' + memberID + '__listTD');
	if (listCell) {
		var lists = {};
		var listBoxes = listCell.getElementsByTagName('input');
		for (var i = 0; i < listBoxes.length; i++) {
			var checkbox = listBoxes.item(i);
			lists[checkbox.value] = (checkbox.checked ? '1' : '0');
		}
		postData.lists = lists;
	}
	jQuery.post(ajaxurl,postData,team_data.api.member_search.updateMemberHandler);
}

team_data.api.member_search.updateMemberHandler = function member_search_updateMemberHandler(memberData)
{
	if (!memberData) return;
	if (memberData.result == 'error') {
		var msg = team_data.fn.getLocText('Error in save');
		if (memberData.error_message) msg += '\n' + memberData.error_message;
		alert(msg);
	}
	else if (!memberData.member) {
		var msg = team_data.fn.getLocText('Save succeeded, but you will need to reload the page to see the new values.');
		alert(msg);
	}
	else {
		var member = memberData.member;
		var memberPos = team_data.member_data.index[member.id];
		if (typeof memberPos == 'number') {
			team_data.member_data.members[memberPos] = member;
		}
		var row = document.getElementById('member_search_result_' + member.id);
		if (row) {
			var currPos = row.getAttribute('resultpos');
			if (team_data.member_data.currentList[currPos]) {
				team_data.member_data.currentList[currPos] = member;
			}
			row.innerHTML = team_data.api.member_search.getRowContents(member,currPos,false);
		}
	}
}

team_data.api.match = {
	"fields": [ 'time', 'level', 'is_league', 'is_postseason', 'our_score', 'opposition_score' ],
	"sharedFields": [ 'date', 'opposition', 'venue', 'season', 'tourney_name' ]
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
			if (isNaN(our_score) || isNaN(opposition_score)) {
				displayDiv.innerHTML = (result != '' ? result : '&nbsp;-&nbsp;');
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
			var lookupName = (team_data.lookups[fieldName] ? team_data.lookups[fieldName] : fieldName);
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
			else if (team_data[lookupName] && team_data[lookupName].nameIndex) {
				fieldValue = team_data[lookupName].nameIndex[fieldValue];
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
			var dataField = ( team_data.sourceMap[displayField] ? team_data.sourceMap[displayField] : displayField );
			if ((control.nodeName != 'SELECT') && team_data[dataField] && team_data[dataField].index && team_data[dataField].index[displayValue]) {
				displayValue = team_data[dataField].index[displayValue];
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
		team_data.api.match.toggleNewMatchDiv(true,false);
		var matchCountControl = document.getElementById('team_data_new_match_shared__matchCount');
		if (matchCountControl) matchCountControl.value = matchCount;
		for (var i = 1; i <= matchCount; i++) {
			var matchForm = document.getElementById('team_data_new_match_' + i);
			if (matchForm) matchForm.style.display = '';
		}
	}
}

team_data.api.match.newTournament = function match_newTournament()
{
	// hide edit div
	team_data.api.match.toggleEditDiv(false);
	team_data.api.match.toggleNewMatchDiv(true,true);
	var matchCountControl = document.getElementById('team_data_new_match_shared__matchCount');
	if (matchCountControl) matchCountControl.value = 1;
	var matchForm = document.getElementById('team_data_new_match_1');
	if (matchForm) matchForm.style.display = '';

}

team_data.api.match.toggleNewMatchDiv = function match_toggleNewMatchDiv(showNewMatch,showTourney)
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
		var oppositionDiv = document.getElementById('team_data_new_match_shared_opposition_div');
		if (oppositionDiv) oppositionDiv.style.display = (showTourney ? 'none' : '');
		var tourneyDiv = document.getElementById('team_data_new_match_shared_tourney_div');
		if (tourneyDiv) tourneyDiv.style.display = (showTourney ? '' : 'none');
		var matchSave = document.getElementById('team_data_new_match__save');
		if (matchSave) matchSave.style.display = (showTourney ? 'none' : '');
		var tourneySave = document.getElementById('team_data_new_tourney__save');
		if (tourneySave) tourneySave.style.display = (showTourney ? '' : 'none');
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

team_data.api.match.saveNewMatches = function match_saveNewMatches(isTourney) {
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
			// copy from sharedFields, and then remove tourney_name or opposition from list, depending on isTourney
			var sharedFieldList = team_data.api.match.sharedFields.slice(0);
			var remField = (isTourney ? 'opposition' : 'tourney_name');
			var remPos = sharedFieldList.indexOf(remField);
			if (remPos > -1) sharedFieldList.splice(remPos,1);
			team_data.api.match.getFieldsFromForm(sharedForm,sharedFieldList,sharedValues,errors,focusList,'shared');
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

team_data.ui.apiList = [ 'venue', 'level', 'list', 'stat', 'team', 'season' ];

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
		else if ((control.nodeName == 'SELECT') || (control.nodeName == 'TEXTAREA')) {
			controlValue = control.value;
		}
	}
	return controlValue;
}

team_data.fn.setControlValue = function(control,value) {
	if (control) {
		if ((control.length > 0) && (typeof control.item == 'function') && (control.nodeName != 'SELECT')) {
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
	var query = document.location.search;
	if (query.charAt(0) == '?') query = query.substring(1);
	query = query.split('&');
	var newQuery = [];
	var pageNum = 0;
	for (var i = 0; i < query.length; i++) {
		if (String(query[i]).indexOf('fixturePage=') != 0) {
			newQuery.push(query[i]);
		}
		else {
			pageNum = parseInt(query[i].substring(query[i].indexOf('=')+1),10);
			if (isNaN(pageNum)) pageNum = 0;
		}
	}
	if (gotoEnd) {
		pageNum = !forward ? 0 : Math.floor(team_data.paging.resultCount/team_data.paging.pageSize);
	}
	else {
		pageNum = pageNum + (forward ? 1 : -1);
	}
	if (pageNum > 0) {
		newQuery.push('fixturePage='+pageNum);
	}
	document.location = url + '?' + newQuery.join('&');
}

team_data.fn.escapeHTML = function(str)
{
	if (typeof str != 'string') return str;
	str = str.replace(/&/g,'&amp;');
	str = str.replace(/</g,'&lt;');
	str = str.replace(/>/g,'&gt;');
	str = str.replace(/\"/g,'&quot;');
	str = str.replace(/\'/g,'&39;');
	str = str.replace(/\u00A0/g,'&nbsp;');
	return str;
}