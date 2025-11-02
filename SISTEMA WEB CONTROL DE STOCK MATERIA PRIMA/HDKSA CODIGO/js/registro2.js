$(document).ready(function(){
  $(".form-wrapper .button").click(function(){
    var button = $(this);
    var currentSection = button.parents(".section");
    var currentSectionIndex = currentSection.index();
    var headerSection = $('.steps li').eq(currentSectionIndex);
    currentSection.removeClass("is-active").next().addClass("is-active");
    headerSection.removeClass("is-active").next().addClass("is-active");

    if(currentSectionIndex === 1){
      $(document).find(".form-wrapper .section").first().addClass("is-active");
      $(document).find(".steps li").first().addClass("is-active");
    }
  });
});