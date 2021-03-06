<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Discussion');

$QnAExists = Gdn::structure()->columnExists('QnA');
$DateAcceptedExists = Gdn::structure()->columnExists('DateAccepted');

Gdn::structure()
    ->column('QnA', array('Unanswered', 'Answered', 'Accepted', 'Rejected'), null, 'index')
    ->column('DateAccepted', 'datetime', true) // The
    ->column('DateOfAnswer', 'datetime', true) // The time to answer an accepted question.
    ->set();

Gdn::structure()
    ->table('Comment')
    ->column('QnA', array('Accepted', 'Rejected'), null)
    ->column('DateAccepted', 'datetime', true)
    ->column('AcceptedUserID', 'int', true)
    ->set();

Gdn::structure()
    ->table('User')
    ->column('CountAcceptedAnswers', 'int', '0')
    ->set();

Gdn::SQL()->replace(
    'ActivityType',
    array('AllowComments' => '0', 'RouteCode' => 'question', 'Notify' => '1', 'Public' => '0', 'ProfileHeadline' => '', 'FullHeadline' => ''),
    array('Name' => 'QuestionAnswer'), true);
Gdn::SQL()->replace(
    'ActivityType',
    array('AllowComments' => '0', 'RouteCode' => 'answer', 'Notify' => '1', 'Public' => '0', 'ProfileHeadline' => '', 'FullHeadline' => ''),
    array('Name' => 'AnswerAccepted'), true);

if ($QnAExists && !$DateAcceptedExists) {
    // Default the date accepted to the accepted answer's date.
    $Px = Gdn::database()->DatabasePrefix;
    $Sql = "update {$Px}Discussion d set DateAccepted = (select min(c.DateInserted) from {$Px}Comment c where c.DiscussionID = d.DiscussionID and c.QnA = 'Accepted')";
    Gdn::SQL()->query($Sql, 'update');
    Gdn::SQL()->update('Discussion')
        ->set('DateOfAnswer', 'DateAccepted', false, false)
        ->put();

    Gdn::SQL()->update('Comment c')
        ->join('Discussion d', 'c.CommentID = d.DiscussionID')
        ->set('c.DateAccepted', 'c.DateInserted', false, false)
        ->set('c.AcceptedUserID', 'd.InsertUserID', false, false)
        ->where('c.QnA', 'Accepted')
        ->where('c.DateAccepted', null)
        ->put();
}


// Define 'Answer' badges

if (Gdn::addonManager()->isEnabled('badges', \Vanilla\Addon::TYPE_ADDON) && c('Plugins.QnA.Badges', true)) {
    $this->Badges = true;
}

if ($this->Badges && class_exists('BadgeModel')) {
    $BadgeModel = new BadgeModel();

    // Answer Counts
    $BadgeModel->define(array(
        'Name' => 'First Answer',
        'Slug' => 'answer',
        'Type' => 'UserCount',
        'Body' => 'Answering questions is a great way to show your support for a community!',
        'Photo' => 'http://badges.vni.la/100/answer.png',
        'Points' => 2,
        'Attributes' => array('Column' => 'CountAcceptedAnswers'),
        'Threshold' => 1,
        'Class' => 'Answerer',
        'Level' => 1,
        'CanDelete' => 0
    ));
    $BadgeModel->define(array(
        'Name' => '5 Answers',
        'Slug' => 'answer-5',
        'Type' => 'UserCount',
        'Body' => 'Your willingness to share knowledge has definitely been noticed.',
        'Photo' => 'http://badges.vni.la/100/answer-2.png',
        'Points' => 3,
        'Attributes' => array('Column' => 'CountAcceptedAnswers'),
        'Threshold' => 5,
        'Class' => 'Answerer',
        'Level' => 2,
        'CanDelete' => 0
    ));
    $BadgeModel->define(array(
        'Name' => '25 Answers',
        'Slug' => 'answer-25',
        'Type' => 'UserCount',
        'Body' => 'Looks like you&rsquo;re starting to make a name for yourself as someone who knows the score!',
        'Photo' => 'http://badges.vni.la/100/answer-3.png',
        'Points' => 5,
        'Attributes' => array('Column' => 'CountAcceptedAnswers'),
        'Threshold' => 25,
        'Class' => 'Answerer',
        'Level' => 3,
        'CanDelete' => 0
    ));
    $BadgeModel->define(array(
        'Name' => '50 Answers',
        'Slug' => 'answer-50',
        'Type' => 'UserCount',
        'Body' => 'Why use Google when we could just ask you?',
        'Photo' => 'http://badges.vni.la/100/answer-4.png',
        'Points' => 10,
        'Attributes' => array('Column' => 'CountAcceptedAnswers'),
        'Threshold' => 50,
        'Class' => 'Answerer',
        'Level' => 4,
        'CanDelete' => 0
    ));
    $BadgeModel->define(array(
        'Name' => '100 Answers',
        'Slug' => 'answer-100',
        'Type' => 'UserCount',
        'Body' => 'Admit it, you read Wikipedia in your spare time.',
        'Photo' => 'http://badges.vni.la/100/answer-5.png',
        'Points' => 15,
        'Attributes' => array('Column' => 'CountAcceptedAnswers'),
        'Threshold' => 100,
        'Class' => 'Answerer',
        'Level' => 5,
        'CanDelete' => 0
    ));
    $BadgeModel->define(array(
        'Name' => '250 Answers',
        'Slug' => 'answer-250',
        'Type' => 'UserCount',
        'Body' => 'Is there *anything* you don&rsquo;t know?',
        'Photo' => 'http://badges.vni.la/100/answer-6.png',
        'Points' => 20,
        'Attributes' => array('Column' => 'CountAcceptedAnswers'),
        'Threshold' => 250,
        'Class' => 'Answerer',
        'Level' => 6,
        'CanDelete' => 0
    ));
}

// Define 'Accept' reaction

if (Gdn::addonManager()->isEnabled('Reactions', \Vanilla\Addon::TYPE_ADDON) && c('Plugins.QnA.Reactions', true)) {
    $this->Reactions = true;
}

if ($this->Reactions && class_exists('ReactionModel')) {
    $Rm = new ReactionModel();

    if (Gdn::structure()->table('ReactionType')->columnExists('Hidden')) {
        $points = 3;
        if (c('QnA.Points.Enabled', false)) {
            $points = c('QnA.Points.AcceptedAnswer', 1);
        }

        // AcceptAnswer
        $Rm->defineReactionType([
            'UrlCode' => 'AcceptAnswer',
            'Name' => 'Accept Answer',
            'Sort' => 0,
            'Class' => 'Positive',
            'IncrementColumn' => 'Score',
            'IncrementValue' => 5,
            'Points' => $points,
            'Permission' => 'Garden.Curation.Manage',
            'Hidden' => 1,
            'Description' => "When someone correctly answers a question, they are rewarded with this reaction."
        ]);
    }

    Gdn::structure()->reset();
}
