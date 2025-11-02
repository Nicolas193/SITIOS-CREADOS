$(document).ready(function(){
  $(".form-wrapper .button").click(function(){
    var button = $(this);
    var currentSection = button.parents(".section");
    var currentSectionIndex = currentSection.index();
    var headerSection = $('.steps li').eq(currentSectionIndex);
    currentSection.removeClass("is-active").next().addClass("is-active");
    headerSection.removeClass("is-active").next().addClass("is-active");

    if(currentSectionIndex === 2){
      $(document).find(".form-wrapper .section").first().addClass("is-active");
      $(document).find(".steps li").first().addClass("is-active");
    }
  });
});

$(document).ready(function(){
  $(".form-wrapper .back-button").click(function(){ // Cambia la clase del botón al botón "volver"
    var button = $(this);
    var currentSection = button.parents(".section");
    var currentSectionIndex = currentSection.index();
    var headerSection = $('.steps li').eq(currentSectionIndex);
    
    if(currentSectionIndex === 0){ // Si es la primera sección, no hacer nada
      return;
    }
    
    currentSection.removeClass("is-active").prev().addClass("is-active"); // Retroceder a la sección anterior
    headerSection.removeClass("is-active").prev().addClass("is-active"); // Retroceder al elemento de la lista de pasos anterior
  });
});

const tanques = {
  1: "1.jpg",
  2: "2.jpg",
  3: "3.jpg",
  4: "4.jpg",
  5: "A.jpg",
  6: "B.jpg",
  7: "C.jpg",
  8: "E.jpg",
  9: "11.jpg",
  10: "14.jpg",
  11: "15.jpg",
  12: "16.jpg",
  13: "18.jpg",
  14: "19.jpg",
  15: "21.jpg",
  16:"22.jpg",
  17:"23.jpg",
  18: "24.jpg",
  19:"25.jpg",
  20:"26.jpg",
  21:"31.jpg",
  22:"32.jpg",
  23:"33.jpg",
  24:"34.jpg",
  25:"30.jpg"
};

const r1 = document.getElementById("r1");
const r2 = document.getElementById("r2");
const r3 = document.getElementById("r3");
const r4 = document.getElementById("r4");
const r5 = document.getElementById("r5");
const r6 = document.getElementById("r6");
const r7 = document.getElementById("r7");
const r8 = document.getElementById("r8");
const r9 = document.getElementById("r9");
const r10 = document.getElementById("r10");
const r11 = document.getElementById("r11");
const r12 = document.getElementById("r12");
const r13 = document.getElementById("r13");
const r14 = document.getElementById("r14");
const r15 = document.getElementById("r15");
const r16 = document.getElementById("r16");
const r17 = document.getElementById("r17");
const r18 = document.getElementById("r18");
const r19 = document.getElementById("r19");
const r20 = document.getElementById("r20");
const r21 = document.getElementById("r21");
const r22 = document.getElementById("r22");
const r23 = document.getElementById("r23");
const r24 = document.getElementById("r24");
const r25 = document.getElementById("r25");
const imgTanque = document.getElementById("img-tanque");

r1.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[1]}`;
});

r2.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[2]}`;
});

r3.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[3]}`;
});

r4.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[4]}`;
});


r5.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[5]}`;
});

r6.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[6]}`;
});

r7.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[7]}`;
});

r8.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[8]}`;
});

r9.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[9]}`;
});


r10.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[10]}`;
});


r11.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[11]}`;
});


r12.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[12]}`;
});

r13.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[13]}`;
});

r14.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[14]}`;
});

r15.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[15]}`;
});

r16.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[16]}`;
});



r17.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[17]}`;
});



r18.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[18]}`;
});



r19.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[19]}`;
});


r20.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[20]}`;
});

r21.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[21]}`;
});

r22.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[22]}`;
});

r23.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[23]}`;
});

r24.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[24]}`;
});

r25.addEventListener("change", function() {
  imgTanque.src = `../imagenes/${tanques[25]}`;
});
