<?php if (!defined('APPLICATION')) { exit(); } ?>
<div class="DataBox DataBox-AcceptedAnswers"><span id="accepted"></span>
    <h2 class="CommentHeading"><?php echo plural(count($sender->data('Answers')), 'Best Answer', 'Best Answers'); ?></h2>
    <ul class="MessageList DataList AcceptedAnswers">
        <?php
        foreach ($sender->data('Answers') as $Row) {
            $sender->EventArguments['Comment'] = $Row;

            ob_start();
            writeComment($Row, $sender, Gdn::session(), 0);
            $commentMarkup = ob_get_clean();

            if (c('QnA.AcceptedAnswers.Filter', true) == false) {
                $commentMarkup = preg_replace('/id="Comment_\d+"/', '', $commentMarkup);
            }

            echo $commentMarkup;
        }
        ?>
    </ul>
</div>
