<h2>Manage Groups</h2>

{{ content() }}

<ul class="pager">
    <!--<li class="pull-right">-->
        <!--{{ link_to("group/new", "Create users", "class": "btn btn-primary") }}-->
    <!--</li>-->
</ul>

{% for group in page.items %}
{% if loop.first %}
{{ form('groups/delete', 'class': 'form-inline', 'method': 'post') }}
<table class="table table-bordered table-striped" align="center">
    <thead>
    <tr>
    	<td style="width: 36px;"><input type="checkbox" class="itemCheckbox" name="checkall" onchange="Util.toggle_checkbox(this, 'item[]')"></td>
        <th>Group ID</th>
        <th>Owner ID</th>
        <th>Owner Name</th>
        <th>Group Name</th>
        <th style="width: 118px;">Manage</th>
    </tr>
    </thead>
    {% endif %}
    <tbody>
    <tr>
        <td><input type="checkbox" class="itemCheckbox" name="item[]" value="{{ group.group_id }}"></td>
        <td>{{ group.group_id }}</td>
        <td>{{ group.owner_id }}</td>
        <td>{{ group.user_name }}</td>
        <td>{{ group.group_name }}</td>
        <td style="vertical-align: middle; margin: 0; text-align: center;"><a href="/admin/groups/manage/{{ group.group_id }}" id="manageBtn{{ group.group_id }}" class="btn btn-success" name="manageButton[]"><i class="icon-pencil"></i> Manage</a></td>
    </tr>
    </tbody>
    {% if loop.last %}
    <tbody>
    <tr>
        <td colspan="6" align="right">
            <ul class="pagination">
                <li>{{ link_to("groups/index", '<i class="icon-fast-backward"></i> First') }}</li>
                <li>{{ link_to("groups/index?page=" ~ page.before, '<i class="icon-backward"></i> Previous') }}</li>
                <li>{{ link_to("groups/index?page=" ~ page.next, 'Next <i class="icon-forward"></i>') }}</li>
                <li>{{ link_to("groups/index?page=" ~ page.last, 'Last <i class="icon-fast-forward"></i>') }}</li>
                <li><span class="help-inline">{{ page.current }}/{{ page.total_pages }}</span></li>
            </ul>
        </td>
    </tr>
    <tbody>
</table>
{% endif %}
{% else %}
No group are recorded
{% endfor %}
<hr>
<span style="float: right">
        <input type="Submit" id="deleteBtn" class="btn btn-danger btn-large" value="Delete"
               onclick="return confirm('Are you sure you want to delete this user?');">
</span>
<br><br>
</form>