// Funcionalidad para formularios de servicios
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("cotizacionForm");
  
  if (form) {
    form.addEventListener("submit", handleFormSubmit);
    
    // Validación en tiempo real
    const requiredFields = form.querySelectorAll("[required]");
    requiredFields.forEach(field => {
      field.addEventListener("blur", validateField);
      field.addEventListener("input", clearValidation);
    });
  }
});

// Manejar envío del formulario
function handleFormSubmit(event) {
  event.preventDefault();
  
  const form = event.target;
  const formData = new FormData(form);
  
  // Validar formulario
  if (!validateForm(form)) {
    return;
  }
  
  // Mostrar estado de carga
  const submitButton = form.querySelector('button[type="submit"]');
  const originalText = submitButton.innerHTML;
  submitButton.disabled = true;
  submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
  
  // Simular envío (aquí puedes integrar con tu backend)
  setTimeout(() => {
    // Mostrar mensaje de éxito
    showMessage("¡Gracias por tu cotización! Te contactaremos pronto.", "success");
    
    // Limpiar formulario
    form.reset();
    
    // Restaurar botón
    submitButton.disabled = false;
    submitButton.innerHTML = originalText;
    
    // Scroll al mensaje
    const messageElement = document.querySelector('.success-message');
    if (messageElement) {
      messageElement.scrollIntoView({ behavior: 'smooth' });
    }
  }, 2000);
}

// Validar campo individual
function validateField(event) {
  const field = event.target;
  const value = field.value.trim();
  
  if (field.hasAttribute("required") && !value) {
    showFieldError(field, "Este campo es obligatorio");
    return false;
  }
  
  if (field.type === "email" && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      showFieldError(field, "Por favor ingresa un email válido");
      return false;
    }
  }
  
  if (field.type === "tel" && value) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
    if (!phoneRegex.test(value)) {
      showFieldError(field, "Por favor ingresa un teléfono válido");
      return false;
    }
  }
  
  showFieldSuccess(field);
  return true;
}

// Limpiar validación
function clearValidation(event) {
  const field = event.target;
  clearFieldValidation(field);
}

// Validar formulario completo
function validateForm(form) {
  let isValid = true;
  const requiredFields = form.querySelectorAll("[required]");
  
  requiredFields.forEach(field => {
    if (!validateField({ target: field })) {
      isValid = false;
    }
  });
  
  return isValid;
}

// Mostrar error en campo
function showFieldError(field, message) {
  clearFieldValidation(field);
  
  field.classList.add("is-invalid");
  
  const errorDiv = document.createElement("div");
  errorDiv.className = "invalid-feedback";
  errorDiv.textContent = message;
  
  field.parentNode.appendChild(errorDiv);
}

// Mostrar éxito en campo
function showFieldSuccess(field) {
  clearFieldValidation(field);
  field.classList.add("is-valid");
}

// Limpiar validación de campo
function clearFieldValidation(field) {
  field.classList.remove("is-invalid", "is-valid");
  
  const feedback = field.parentNode.querySelector(".invalid-feedback, .valid-feedback");
  if (feedback) {
    feedback.remove();
  }
}

// Mostrar mensaje general
function showMessage(message, type = "success") {
  // Remover mensajes existentes
  const existingMessages = document.querySelectorAll(".success-message, .error-message");
  existingMessages.forEach(msg => msg.remove());
  
  // Crear nuevo mensaje
  const messageDiv = document.createElement("div");
  messageDiv.className = type === "success" ? "success-message" : "error-message";
  messageDiv.innerHTML = `
    <i class="fas fa-${type === "success" ? "check-circle" : "exclamation-circle"} mr-2"></i>
    ${message}
  `;
  
  // Insertar antes del formulario
  const form = document.getElementById("cotizacionForm");
  if (form) {
    form.parentNode.insertBefore(messageDiv, form);
  }
}

// Funcionalidad adicional para campos específicos
document.addEventListener("DOMContentLoaded", function () {
  // Auto-completar formato de teléfono
  const phoneFields = document.querySelectorAll('input[type="tel"]');
  phoneFields.forEach(field => {
    field.addEventListener("input", function(e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 0) {
        if (value.length <= 2) {
          value = `+56 ${value}`;
        } else if (value.length <= 6) {
          value = `+56 ${value.slice(0, 2)} ${value.slice(2)}`;
        } else if (value.length <= 10) {
          value = `+56 ${value.slice(0, 2)} ${value.slice(2, 6)} ${value.slice(6)}`;
        } else {
          value = `+56 ${value.slice(0, 2)} ${value.slice(2, 6)} ${value.slice(6, 10)}`;
        }
      }
      e.target.value = value;
    });
  });
  
  // Validación de dimensiones para muebles
  const dimensionFields = document.querySelectorAll('input[name="ancho"], input[name="alto"], input[name="profundidad"]');
  dimensionFields.forEach(field => {
    field.addEventListener("input", function(e) {
      const value = parseInt(e.target.value);
      if (value && (value < 1 || value > 1000)) {
        showFieldError(field, "Las dimensiones deben estar entre 1 y 1000 cm");
      } else if (value) {
        clearFieldValidation(field);
      }
    });
  });
  
  // Validación de cantidad para 3D
  const quantityField = document.querySelector('input[name="cantidad"]');
  if (quantityField) {
    quantityField.addEventListener("input", function(e) {
      const value = parseInt(e.target.value);
      if (value && (value < 1 || value > 1000)) {
        showFieldError(field, "La cantidad debe estar entre 1 y 1000");
      } else if (value) {
        clearFieldValidation(field);
      }
    });
  }
  
  // Mostrar/ocultar campos según selección
  const archivo3dField = document.querySelector('select[name="archivo-3d"]');
  const formatoArchivoField = document.querySelector('select[name="formato-archivo"]');
  const notasArchivoField = document.querySelector('textarea[name="notas-archivo"]');
  
  if (archivo3dField) {
    archivo3dField.addEventListener("change", function(e) {
      const hasFile = e.target.value === "si";
      const fileFields = [formatoArchivoField, notasArchivoField];
      
      fileFields.forEach(field => {
        if (field) {
          const parentSection = field.closest('.form-group');
          if (parentSection) {
            parentSection.style.display = hasFile ? "block" : "none";
          }
        }
      });
    });
  }
});

// Función para enviar datos al servidor (ejemplo)
function sendFormData(formData) {
  // Aquí puedes integrar con tu backend
  // Ejemplo con fetch:
  /*
  return fetch('/api/cotizacion', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showMessage("¡Cotización enviada exitosamente!", "success");
    } else {
      showMessage("Error al enviar la cotización. Intenta nuevamente.", "error");
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showMessage("Error de conexión. Intenta nuevamente.", "error");
  });
  */
  
  // Por ahora, solo simulamos el envío
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve({ success: true });
    }, 2000);
  });
}
