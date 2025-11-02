  // opciones fason y trasbordo
  var motivoSelect = document.getElementById("motivo");
  var campoSeleccion = document.getElementById("campo-seleccion");
  var seleccionSelect = document.getElementById("seleccion");
  var fasonOpciones = {
    "alicorp": "ALICORP",
    "insugra": "INSUGRA",
    "unilever": "UNILEVER"
  };
  var trasbordoOpciones = {
    "1": "1",
    "2": "2",
    "3": "3",
    "4": "4",
    "A": "A",
    "B": "B",
    "C": "C",
    "E": "E",
    "11": "11",
    "14": "14",
    "15": "15",
    "16": "16",
    "18": "18",
    "19": "19",
    "21": "21",
    "22": "22",
    "23": "23",
    "24": "24",
    "25": "25",
    "26": "26",
    "30": "30",
    "31": "31",
    "32": "32",
    "33": "33",
    "34": "34"
  };

  var ventaOpcion = {
    "null": "Otros",
    "venta alicorp": "Venta Alicorp",
    "venta insugra": "Venta Insugra",
    "venta unilever": "Venta Unilever"
  };
  motivoSelect.addEventListener("change", function() {
    if (this.value == "fason") {
      seleccionSelect.innerHTML = "";
      for (var opcion in fasonOpciones) {
        var opcionElement = document.createElement("option");
        opcionElement.value = opcion;
        opcionElement.textContent = fasonOpciones[opcion];
        seleccionSelect.appendChild(opcionElement);
      }
      campoSeleccion.style.display = "block";
    } else if (this.value == "trasbordo") {
      seleccionSelect.innerHTML = "";
      for (var opcion in trasbordoOpciones) {
        var opcionElement = document.createElement("option");
        opcionElement.value = opcion;
        opcionElement.textContent = trasbordoOpciones[opcion];
        seleccionSelect.appendChild(opcionElement);
      }
      campoSeleccion.style.display = "block";
    } else if(this.value == "venta"){

            seleccionSelect.innerHTML = "";
      for (var opcion in ventaOpcion) {
        var opcionElement = document.createElement("option");
        opcionElement.value = opcion;
        opcionElement.textContent = ventaOpcion[opcion];
        seleccionSelect.appendChild(opcionElement);
      }

            campoSeleccion.style.display = "block";
       }else{ 
      seleccionSelect.innerHTML = "<option value=\"null\"></option>";
      campoSeleccion.style.display = "none";
    }
  });