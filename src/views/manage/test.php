<h1>Box</h1>

<?php \insolita\wgadminlte\LteBox::begin([
                                             'type'=>\insolita\wgadminlte\LteConst::TYPE_INFO,
                                             'isSolid'=>true,
                                             'boxTools'=>'<button class="btn btn-success btn-xs create_button" ><i class="fa fa-plus-circle"></i> Add</button>',
                                             'tooltip'=>'this tooltip description',
                                             'title'=>'Manage users',
                                             'footer'=>'total 44 active users',
                                         ])?>
ANY BOX CONTENT HERE
<?php \insolita\wgadminlte\LteBox::end()?>

<h1>Tile</h1>

<?php \insolita\wgadminlte\LteBox::begin([
                                             'type'=>\insolita\wgadminlte\LteConst::COLOR_PURPLE,
                                             'tooltip'=>'Useful information!',
                                             'title'=>'Attention!',
                                             'isTile'=>true
                                         ])?>
ANY BOX CONTENT HERE
<?php \insolita\wgadminlte\LteBox::end()?>

<h1>Box with content as property</h1>

<?= \insolita\wgadminlte\LteBox::widget([
                                              'type'=>\insolita\wgadminlte\LteConst::COLOR_MAROON,
                                              'tooltip'=>'Useful information!',
                                              'title'=>'Attention!',
                                              'isTile'=>true,
                                              'body'=>'Some Box content'
                                          ])?>

<h1>CollapseBox (Based on LteBox)</h1>

<?php \insolita\wgadminlte\CollapseBox::begin([
                                                  'type'=>\insolita\wgadminlte\LteConst::TYPE_INFO,
                                                  'collapseRemember' => true,
                                                  'collapseDefault' => false,
                                                  'isSolid'=>true,
                                                  'boxTools'=>'<button class="btn btn-success btn-xs create_button" ><i class="fa fa-plus-circle"></i> Tool Button</button>',
                                                  'tooltip'=>'Tooltip',
                                                  'title'=>'Title',
                                              ])?>
ANY BOX CONTENT HERE
<?php \insolita\wgadminlte\CollapseBox::end()?>

<h1>SmallBox</h1>

<?php echo \insolita\wgadminlte\LteSmallBox::widget([
                                                        'type'=>\insolita\wgadminlte\LteConst::COLOR_LIGHT_BLUE,
                                                        'title'=>'90%',
                                                        'text'=>'Free Space',
                                                        'icon'=>'fa fa-cloud-download',
                                                        'footer'=>'See All <i class="fa fa-hand-o-right"></i>',
                                                        'link'=>\yii\helpers\Url::to("/user/list")
                                                    ]);?>

<h1>InfoBox</h1>

<?php echo \insolita\wgadminlte\LteInfoBox::widget([
                                                       'bgIconColor'=>\insolita\wgadminlte\LteConst::COLOR_AQUA,
                                                       'bgColor'=>'',
                                                       'number'=>100500,
                                                       'text'=>'Test Three',
                                                       'icon'=>'fa fa-bolt',
                                                       'showProgress'=>true,
                                                       'progressNumber'=>66,
                                                       'description'=>'Something about this'
                                                   ])?>


?>
