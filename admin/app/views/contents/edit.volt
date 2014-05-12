<?php

 use Phalcon\Tag as Tag ?>

<?php echo $this->getContent(); ?>

<ul class="pager">
    <li class="previous pull-left">
        <button class="btn btn-large" onclick="history.go(-1);">&larr; Back</button>
    </li>
</ul>

<?php
echo Tag::form(array(
    "id" => "updateContent",
    "contents/update",
    "method" => "post"
));
?>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Content info</h3>
            </div>
            <div class="panel-body info-group">

                <?php $content = $this->view->content ?>

                <p><b>File Name: </b><?php echo $content->content_name ?></p>
                <p><b>File Type: </b><?php echo $content->file_type ?></p>
                <p><b>File Size: </b><?php echo Utils::formatFileSize($content->content_size) ?></p>
                <p><b>Status: </b><?php echo $content->status ?></p>
                <p><b>Uploaded: </b><?php echo $content->uploaded ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Sharing groups</h3>
            </div>
            <table class="table table-custom">
                <thead>
                    <?php $memList = $this->view->memList ?>
                    <?php if (count($memList) > 0) { ?>
                        <tr>
                            <th style="width: 10%;">
                                <?php
                                echo Tag::checkField(array(
                                    "check_group",
                                    "onchange" => "Util.toggle_checkbox(this, 'group[]')"
                                ));
                                ?>
                            </th>
                            <th>Name</th>
                            <th>Owner</th>
                        </tr>
                    <?php } else { ?>
                        <tr>
                            <th colspan="3">
                                <span>You haven't joined any group yet. <?php echo Tag::linkTo("groups/index", "Join group") ?> first.</span>
                            </th>
                        </tr>
                    <?php } ?>
                </thead>
                <tbody>
                    <?php
                    $groups = $this->view->groups;

                    foreach ($groups as $group) {
                        if (in_array($group->groups->group_id, $this->view->memList)) {
                            ?>
                            <tr>
                                <td style="width: 10%;">
                                    <?php
                                    // Check the checkbox incase content has shared to group
                                    if (in_array($group->groups->group_id, $this->view->shared_list)) {
                                        echo Tag::checkField(array(
                                            "group[]",
                                            "value" => $group->groups->group_id,
                                            "checked" => TRUE
                                        ));
                                    } else {
                                        echo Tag::checkField(array(
                                            "group[]",
                                            "value" => $group->groups->group_id
                                        ));
                                    }
                                    ?>
                                </td>
                                <td><?php echo $group->groups->group_name ?></td>
                                <td><?php echo $group->user_name ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>


        </div>
    </div>

</div>
<div class="btn-toolbar" role="toolbar">
    <button type="submit" class="btn btn-lg btn-success"><span class="icon icon-ok"></span> Save</button>
    <?php echo $this->tag->linkTo(array("contents/index", '<span class="icon icon-ban-circle"></span> Cancel', "role" => "button", "class" => "btn btn-lg btn-warning")) ?>
</div>
<?php
echo Tag::hiddenField(array(
    "content_id",
    "value" => $this->view->content_id
));
?>

<?php echo Tag::endForm() ?>

