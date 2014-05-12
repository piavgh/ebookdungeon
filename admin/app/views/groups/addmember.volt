<h2>Select users to add in group {{ group.group_name }}</h2>

{{ content() }}

<ul class="pager">

</ul>

{{ form('groups/search/' ~ group.group_id, 'class': 'form-inline', 'method': 'post') }}
    	<input type="hidden" name="group_id" value="<?php echo $group->group_id ?>">
    	<input type="text" id="member_name" name="member_name" class="input-medium search-query" placeholder="Enter user name here" style="width: 230px;">
    	<button type="submit" class="btn btn-success">Search</button>
</form>

{% for user in page.items %}
{% if loop.first %}
{{ form('groups/deletemember', 'class': 'form-inline', 'method': 'post') }}
<table class="table table-bordered table-striped" align="center">
    <thead>
    <tr>
    	<td style="width: 36px;"><input type="checkbox" class="itemCheckbox" name="checkall" onchange="Util.toggle_checkbox(this, 'item[]')"></td>
        <th style="width: 68px;">User ID</th>
        <th>User Name</th>
        <th>Email</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Phone</th>
        <th style="width: 48px;">Action</th>
    </tr>
    </thead>
    {% endif %}
    <tbody>
    <tr>
    	<?php if (!in_array($user->user_id, $array1)) { ?>
        <td><input type="checkbox" class="itemCheckbox" name="item[]" value="{{ user.user_id }}"></td>
        <td>{{ user.user_id }}</td>
        <td>{{ user.user_name }}</td>
        <td>{{ user.email }}</td>
        <td>{{ user.first_name }}</td>
        <td>{{ user.last_name }}</td>
        <td>{{ user.phone }}</td>
        <td><?php echo $this->tag->linkTo(array("groups/saveusertogroup/$user->user_id/$group->group_id", '<span class="icon icon-plus"></span> Add', "role" => "button", 'class' => 'btn btn-success')) ?></td>
    	<?php } ?>
    </tr>
    </tbody>
    {% if loop.last %}
    <tbody>
    <tr>
        <td colspan="8" align="right">
            <ul class="pagination">
                <li><?php echo $this->tag->linkTo(array("groups/addmember/$group->group_id", '<span class="icon icon-fast-backward"></span> First', "role" => "button")) ?></li>
                <li><?php echo $this->tag->linkTo(array("groups/addmember/$group->group_id?page=" . $page->before, '<span class="icon icon-backward"></span> Previous', "role" => "button")) ?></li>
                <li><?php echo $this->tag->linkTo(array("groups/addmember/$group->group_id?page=" . $page->next, 'Next <span class="icon icon-forward"></span>', "role" => "button")) ?></li>
                <li><?php echo $this->tag->linkTo(array("groups/addmember/$group->group_id?page=" . $page->last, 'Last <span class="icon icon-fast-forward"></span>', "role" => "button")) ?></li>
                <li><span class="help-inline"><?php echo $page->current, "/", $page->total_pages ?></span></li>
            </ul>
        </td>
    </tr>
    <tbody>
</table>
{% endif %}
{% else %}
No user are recorded
{% endfor %}
<br><br>