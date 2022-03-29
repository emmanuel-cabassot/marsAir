var isnavBar = false;
function navBar() {
  isnavBar = !isnavBar;
  if (isnavBar) {
    $(".navigation").css("left", "0%");
    $(".navButton ul li:nth-child(2)").css("display", "none");

    $(".navButton ul li:nth-child(1)").css("margin-bottom", "-9px");
    $(".navButton ul li:nth-child(1)").css("transform", "rotate(45deg)");

    // $(".navButton ul li:nth-child(3)").css("margin-bottom", "-10px");
    $(".navButton ul li:nth-child(3)").css("transform", "rotate(-45deg)");
  } else {
    $(".navigation").css("left", "-100%");
    $(".navButton ul li:nth-child(2)").css("display", "block");

    $(".navButton ul li:nth-child(1)").css("margin-bottom", "0");
    $(".navButton ul li:nth-child(1)").css("transform", "rotate(0deg)");

    // $(".navButton ul li:nth-child(3)").css("margin-bottom", "0");
    $(".navButton ul li:nth-child(3)").css("transform", "rotate(0deg)");
  }
}

var issignin = false;
var issignup = false;

function showSignin() {
  issignin = !issignin;
  setTimeout(() => {
    if (issignin) {
      $(".signin").css("bottom", "0%");
    }
  }, 600);
  if (!issignin) {
    $(".signin").css("bottom", "-100%");
  }
}

function showSignup() {
  issignup = !issignup;
  setTimeout(() => {
    if (issignup) {
      $(".signin").css("bottom", "0%");
    }
  }, 600);
  if (!issignup) {
    $(".signin").css("bottom", "-100%");
  }
}
