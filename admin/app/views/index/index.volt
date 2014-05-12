{{ content() }}

<div class="login-or-signup">
    <div class="row">

        <div class="span6">
            <div class="page-header">
                <h2>Log In</h2>
            </div>
            {{ form('session/start', 'class': 'form-inline' , 'method': 'post') }}
            <fieldset>
                <div class="control-group">
                    <label class="control-label" for="name">Username</label>
                    <div class="controls">
                        {{ text_field('name', 'size': "30", 'class': "input-xlarge") }}
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="password">Password</label>
                    <div class="controls">
                        {{ password_field('password', 'size': "30", 'class': "input-xlarge") }}
                    </div>
                </div>
                <div class="form-actions">
                    {{ submit_button('Log In', 'class': 'btn btn-primary btn-large') }}
                </div>
            </fieldset>
            </form>
        </div>

    </div>
</div>
