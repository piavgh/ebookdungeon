{{ content() }}

<div class="profile left">
    {{ form('profile/changepass', 'id': 'changepassForm', 'onbeforesubmit': 'return false') }}

    <div class="clearfix">
        <label for="current_password">Current password:</label>
        <div class="input">
            {{ password_field("current_password", "size": "30", "class": "span6") }}
            <div class="alert" id="current_password_alert">
                <strong>Warning!</strong> Please enter your current password
            </div>
        </div>
    </div>

    <div class="clearfix">
        <label for="new_password">New password:</label>
        <div class="input">
            {{ password_field("new_password", "size": "30", "class": "span6") }}
            <div class="alert" id="new_password_alert">
                <strong>Warning!</strong> Please enter your current password
            </div>
        </div>
    </div>

    <div class="clearfix">
        <label for="confirm_password">Confirm password:</label>
        <div class="input">
            {{ password_field("confirm_password", "size": "30", "class": "span6") }}
            <div class="alert" id="confirm_password_alert">
                <strong>Warning!</strong> Please enter your current password
            </div>
        </div>
    </div>

    <div class="clearfix">
        <input type="button" value="Update Password" class="btn btn-primary btn-large btn-info" onclick="ChangePass.validate()">
        &nbsp;
        {{ link_to('profile/index', 'Cancel') }}
    </div>
    </form>
</div>