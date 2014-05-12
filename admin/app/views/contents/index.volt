<h2>Manage Contents</h2>

{{ content() }}
<br>
{% for content in page.items %}
{% if loop.first %}
{{ form('contents/deleteselect', 'class': 'form-inline', 'method': 'post') }}
<table class="table table-bordered table-striped" align="center">
    <thead>
    <tr>
        <td><input type="checkbox" class="itemCheckbox" name="checkall" onchange="Util.toggle_checkbox(this, 'item[]')"></td>
        <th>ID</th>
        <th>Name</th>
        <th>Owner ID</th>
        <th>Owner Name</th>
        <th>File Type</th>
        <th>Size</th>
        <th>Status</th>
        <th>Created Time</th>
        <th>Uploaded Time</th>
        <th style="vertical-align: middle; margin: 0; text-align: center;">Action</th>
    </tr>
    </thead>
    {% endif %}
    <tbody>
    <tr>
        <td><input type="checkbox" id="checkbox{{ content.contents.content_id }}" class="itemCheckbox" name="item[]" value="{{ content.contents.content_id }}" onchange="Util.toggle_individual_checkbox(this, {{ content.contents.content_id }})"></td>
        <td>{{ content.contents.content_id }}</td>
        <td>{{ link_to("contents/show/" ~ content.contents.content_id, content.contents.content_name) }}</td>
        <td>{{ content.contents.owner_id }}</td>
        <td>{{ content.user_name }}</td>
        <td>{{ content.contents.file_type }}</td>
        <td><?php echo Utils::formatFileSize($content->contents->content_size) ?></td>
        <td>
        	{% if (content.contents.status == "shared") %}
        	<span class="label label-success">{{ content.contents.status }}</span>
        	{% else %}
        	<span class="label label-default">{{ content.contents.status }}</span>
        	{% endif %}
        </td>
        <td>{{ content.contents.created }}</td>
        <td>{{ content.contents.uploaded }}</td>
        <td style="vertical-align: middle; margin: 0; text-align: center;"><a href="/admin/contents/edit/{{ content.contents.content_id }}" id="modifyBtn{{ content.contents.content_id }}" class="btn" name="modifyButton[]" disabled><i class="icon-pencil"></i> Modify</a></td>
        {# <td style="vertical-align: middle; margin: 0; text-align: center;">{{ link_to("contents/edit/" ~ content.contents.content_id, '<i class="icon-pencil"></i> Modify', "id": "modifyBtn{{ content.contents.content_id }}", "class": "btn") }}</td> #}
    </tr>
    </tbody>
    {% if loop.last %}
    <tbody>
    <tr>
        <td colspan="10" align="right">
            <ul class="pagination">
                <li>{{ link_to("contents/index", '<i class="icon-fast-backward"></i> First') }}</li>
                <li>{{ link_to("contents/index?page=" ~ page.before, '<i class="icon-backward"></i> Previous') }}</li>
                <li>{{ link_to("contents/index?page=" ~ page.next, 'Next <i class="icon-forward"></i>') }}</li>
                <li>{{ link_to("contents/index?page=" ~ page.last, 'Last <i class="icon-fast-forward"></i>') }}</li>
                <li><span class="help-inline">{{ page.current }}/{{ page.total_pages }}</span></li>
            </ul>
        </td>
        <td style="vertical-align: middle; margin: 0; text-align: center;"><input type="Submit" id="deleteBtn" class="btn btn-danger btn-large" value="Delete"
               onclick="return confirm('Are you sure you want to delete this item?');"></td>
    </tr>
    <tbody>
</table>
{% endif %}
{% else %}
No contents are recorded
{% endfor %}
<hr>

<br><br>
</form>