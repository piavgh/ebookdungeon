{{ form('account/create', 'method': 'post') }}

<ul class="pager">
    <li class="previous pull-left">
        {{ link_to("account", "&larr; Go Back") }}
    </li>
</ul>

{{ content() }}

<div class="left scaffold">
    <h2>Create users</h2>

    <div class="clearfix">
        <label for="user_name">Username:</label>
        {{ form.render("user_name") }}
    </div>

    <div class="clearfix">
        <label for="password">Password:</label>
        {{ form.render("password") }}
    </div>

    <div class="clearfix">
        <label for="password_confirm">Confirm Password:</label>
        {{ form.render("password_confirm") }}
    </div>

    <div class="clearfix">
        <label for="email">Email:</label>
        {{ form.render("email") }}
    </div>

    <div class="clearfix">
        <label for="first_name">First Name:</label>
        {{ form.render("first_name") }}
    </div>

    <div class="clearfix">
        <label for="last_name">Last Name:</label>
        {{ form.render("last_name") }}
    </div>

    <div class="clearfix">
        <label for="phone">Phone Number:</label>
        {{ form.render("phone") }}
    </div>

    <div class="clearfix">
        <label for="expiration">Expiration Date:</label>
        {{ form.render("expiration") }}
    </div>
	
	{{ submit_button("Create", "id": "btnCreate", "class": "btn btn-success") }}
</div>
</form>
