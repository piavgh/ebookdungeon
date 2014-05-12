<h2>Manage Accounts</h2>

{{ content() }}

<ul class="pager">
    <li class="pull-right">
        {{ link_to("account/new", "Create users", "id": "btnCreate", "class": "btn btn-large btn-success") }}
    </li>
</ul>

{% for user in page.items %}
{% if loop.first %}
{{ form('account/delete', 'class': 'form-inline', 'method': 'post') }}
<table class="table table-bordered table-striped" align="center">
    <thead>
    <tr>
        <td><input type="checkbox" class="itemCheckbox" name="checkall" onchange="Util.toggle_checkbox(this, 'item[]')"></td>
        <th>User ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Phone Number</th>
        <th>Status</th>
        <th colspan="3" style="text-align:center; width:10%;">Action</th>
    </tr>
    </thead>
    {% endif %}
    <tbody>
    <tr>
        <td><input type="checkbox" class="itemCheckbox" name="item[]" value="{{ user.user_id }}"></td>
        <td>{{ user.user_id }}</td>
        <td>{{ user.user_name }}</td>
        <td>{{ user.email }}</td>
        <td>{{ user.first_name }}</td>
        <td>{{ user.last_name }}</td>
        <td>{{ user.phone }}</td>
        <td>
        	{% if (user.status == "active") %}
        	<span class="label label-success">{{ user.status }}</span>
        	{% else %}
        	<span class="label label-important">{{ user.status }}</span>
        	{% endif %}
        </td>
        <td style="text-align:center; width:10%;">{{ link_to("account/enable/" ~ user.user_id, '<i class="icon-plus"></i> Enable', "class": "btn")
            }}
        </td>
        <td style="text-align:center; width:10%;">{{ link_to("account/disable/" ~ user.user_id, '<i class="icon-minus"></i> Disable', "class":
            "btn") }}
        </td>
        <td style="text-align:center; width:10%;">{{ link_to("account/changemaximum/" ~ user.user_id, '<i class="icon-edit"></i> Manage Space', "class":
            "btn") }}
        </td>
    </tr>
    </tbody>
    {% if loop.last %}
    <tbody>
    <tr>
        <td colspan="11" align="right">
            <ul class="pagination">
                <li>{{ link_to("account/index", '<i class="icon-fast-backward"></i> First') }}</li>
                <li>{{ link_to("account/index?page=" ~ page.before, '<i class="icon-backward"></i> Previous') }}</li>
                <li>{{ link_to("account/index?page=" ~ page.next, 'Next <i class="icon-forward"></i>') }}</li>
                <li>{{ link_to("account/index?page=" ~ page.last, 'Last <i class="icon-fast-forward"></i>') }}</li>
                <li><span class="help-inline">{{ page.current }}/{{ page.total_pages }}</span></li>
            </ul>
        </td>
    </tr>
    <tbody>
</table>
{% endif %}
{% else %}
No users are recorded
{% endfor %}
<hr>
<span style="float: right">
        <input type="Submit" id="deleteBtn" class="btn btn-danger btn-large" value="Delete"
               onclick="return confirm('Are you sure you want to delete this user?');">
</span>
<br><br>
</form>