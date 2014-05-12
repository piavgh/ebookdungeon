{{ content() }}

<div class="profile left">
    {{ form('profile/changeemail', 'id': 'changeemailForm', 'onbeforesubmit': 'return false') }}
    <div class="clearfix">
        <label for="email">Enter new email:</label>
        <div class="input">
            {{ text_field("email", "size": "30", "class": "span6") }}
            <div class="alert" id="name_alert">
                <strong>Warning!</strong> Please enter your new email
            </div>
        </div>
    </div>
    <div class="clearfix">
        <label for="password">Enter current password:</label>
        <div class="input">
            {{ password_field("password", "size": "30", "class": "span6") }}
            <div class="alert" id="email_alert">
                <strong>Warning!</strong> Please enter your current password
            </div>
        </div>
    </div>
    <div class="clearfix">
        <input type="button" value="Update Email" class="btn btn-primary btn-large btn-info" onclick="ChangeEmail.validate()">
        &nbsp;
        {{ link_to('profile/index', 'Cancel') }}
    </div>
    </form>
</div>