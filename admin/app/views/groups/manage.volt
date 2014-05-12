<h2>Members of group {{ group.group_name }}</h2>

{{ content() }}

<ul class="pager">

</ul>

{% for member in page.items %}
{% if loop.first %}
{{ form('groups/deletemember', 'class': 'form-inline', 'method': 'post') }}
<table class="table table-bordered table-striped" align="center">
    <thead>
    <tr>
    	<td style="width: 36px;"><input type="checkbox" class="itemCheckbox" name="checkall" onchange="Util.toggle_checkbox(this, 'item[]')"></td>
        <th>Member ID</th>
        <th>Member Name</th>
        <th>Member Status</th>
        <th colspan="3" style="text-align:center; width:10%;">Action</th>
    </tr>
    </thead>
    {% endif %}
    <tbody>
    <tr>
        <td><input type="checkbox" class="itemCheckbox" name="item[]" value="{{ member.member_id }}"></td>
        <td>{{ member.member_id }}</td>
        <td>{{ member.user_name }}</td>
        <td>
        	{% if (member.member_status == "active") %}
        	<span class="label label-success">{{ member.member_status }}</span>
        	{% elseif (member.member_status == "pending") %}
        	<span class="label label-warning">{{ member.member_status }}</span>
        	{% else %}
        	<span class="label label-important">{{ member.member_status }}</span>
        	{% endif %}
        </td>
        <td style="text-align:center; width:10%;"><?php echo $this->tag->linkTo(array("groups/activemember/$member->member_id/$group->group_id", '<span class="icon icon-plus"></span> Active', "role" => "button", 'class' => 'btn')) ?>
        </td>
        <td style="text-align:center; width:10%;"><?php echo $this->tag->linkTo(array("groups/rejectmember/$member->member_id/$group->group_id", '<span class="icon icon-minus"></span> Reject', "role" => "button", 'class' => 'btn')) ?>
        </td>
        <td style="text-align:center; width:10%;"><?php echo $this->tag->linkTo(array("groups/deletemember/$member->member_id/$group->group_id", '<span class="icon icon-remove"></span> Delete', "role" => "button", 'class' => 'btn btn-danger')) ?>
        </td>
    </tr>
    </tbody>
    {% if loop.last %}
    <tbody>
    <tr>
        <td colspan="7" align="right">
            <ul class="pagination">
                <li><?php echo $this->tag->linkTo(array("groups/manage/$group->group_id", '<span class="icon icon-fast-backward"></span> First', "role" => "button")) ?></li>
                <li><?php echo $this->tag->linkTo(array("groups/manage/$group->group_id?page=" . $page->before, '<span class="icon icon-backward"></span> Previous', "role" => "button")) ?></li>
                <li><?php echo $this->tag->linkTo(array("groups/manage/$group->group_id?page=" . $page->next, 'Next <span class="icon icon-forward"></span>', "role" => "button")) ?></li>
                <li><?php echo $this->tag->linkTo(array("groups/manage/$group->group_id?page=" . $page->last, 'Last <span class="icon icon-fast-forward"></span>', "role" => "button")) ?></li>
                <li><span class="help-inline"><?php echo $page->current, "/", $page->total_pages ?></span></li>
            </ul>
        </td>
    </tr>
    <tbody>
</table>
{% endif %}
{% else %}
No member are recorded
{% endfor %}
<hr>
<span style="float: left">
	<?php echo $this->tag->linkTo(array("groups/addmember/$group->group_id", '<span class="icon icon-plus"></span> Add members', "role" => "button", 'class' => 'btn btn-large btn-success')) ?>
</span>
<br><br>