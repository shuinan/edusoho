import DoTestBase from '../widget/do-test-base';


class ShowResult extends DoTestBase {
  constructor($container) {
    super($container);
  }
}

new ShowResult($('.js-task-testpaper-body'));

$('.js-testpaper-redo-timer').timer({
  countdown:true,
  duration: $('.js-testpaper-redo-timer').data('time'),
  format: '%H:%M:%S',
  callback: function() {
    console.log('timer');
    $('#finishPaper').attr('disabled',false);
  },
  repeat: true,
  start: function() {
    self.usedTime = 0;
  }
});