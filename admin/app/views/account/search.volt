{{ content() }}

<ul class="pager">
    <li class="previous pull-left">
        {{ link_to("account/index", "&larr; Go Back") }}
    </li>
    <li class="pull-right">
        {{ link_to("account/new", "Create users", "class": "btn btn-primary") }}
    </li>
</ul>

{% for user in page.items %}
{% if loop.first %}
<table class="table table-bordered table-striped" align="center">
    <thead>
    <tr>
        <th>User ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Phone Number</th>
        <th>Status</th>
        <th colspan="3">Action</th>
    </tr>
    </thead>
    {% endif %}
    <tbody>
    <tr>
        <td>{{ user.user_id }}</td>
        <td>{{ user.user_name }}</td>
        <td>{{ user.email }}</td>
        <td>{{ user.first_name }}</td>
        <td>{{ user.last_name }}</td>
        <td>{{ user.phone }}</td>
        <td>{{ user.status }}</td>
        <td style="text-align:center; width:10%;">{{ link_to("account/enable/" ~ user.user_id, 'Enable', "class": "btn")
            }}
        </td>
        <td style="text-align:center; width:10%;">{{ link_to("account/disable/" ~ user.user_id, 'Disable', "class":
            "btn") }}
        </td>
        <td style="text-align:center; width:10%;">{{ link_to("account/delete/" ~ user.user_id, 'Delete', "class":
            "btn btn-danger") }}
        </td>
    </tr>
    </tbody>
    {% if loop.last %}
    <tbody>
    <tr>
        <td colspan="10" align="right">
            <ul class="pagination">
                <li>{{ link_to("account/search", '<i class="icon-fast-backward"></i> First') }}</li>
                <li>{{ link_to("account/search?page=" ~ page.before, '<i class="icon-backward"></i> Previous') }}</li>
                <li>{{ link_to("account/search?page=" ~ page.next, 'Next <i class="icon-forward"></i>') }}</li>
                <li>{{ link_to("account/search?page=" ~ page.last, 'Last <i class="icon-fast-forward"></i>') }}</li>
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
