// Funcionalidad para formulario de contacto
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("contactoForm");
  const tipoConsultaSelect = document.getElementById("tipo-consulta");
  
  if (form) {
    form.addEventListener("submit", handleContactoSubmit);
    
    // Validación en tiempo real
    const requiredFields = form.querySelectorAll("[required]");
    requiredFields.forEach(field => {
      field.addEventListener("blur", validateField);
      field.addEventListener("input", clearValidation);
    });
    
    // Cambiar campos según tipo de consulta
    if (tipoConsultaSelect) {
      tipoConsultaSelect.addEventListener("change", handleTipoConsultaChange);
    }
  }
});

// Manejar envío del formulario de contacto
function handleContactoSubmit(event) {
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
  
  // Enviar datos a la API
  sendContactoData(formData)
    .then(response => {
      if (response.success) {
        // Mostrar mensaje de éxito
        showMessage("¡Gracias por tu mensaje! Te contactaremos pronto. Tu solicitud ha sido registrada.", "success");
        
        // Limpiar formulario
        form.reset();
        
        // Scroll al mensaje
        const messageElement = document.querySelector('.success-message');
        if (messageElement) {
          messageElement.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        showMessage("Hubo un problema al enviar tu mensaje. Por favor, intenta nuevamente.", "error");
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showMessage("Error de conexión. Por favor, verifica tu conexión e intenta nuevamente.", "error");
    })
    .finally(() => {
      // Restaurar botón
      submitButton.disabled = false;
      submitButton.innerHTML = originalText;
    });
}

// Manejar cambio de tipo de consulta
function handleTipoConsultaChange(event) {
  const tipoConsulta = event.target.value;
  const presupuestoField = document.getElementById("presupuesto");
  const plazoField = document.getElementById("plazo");
  
  // Mostrar/ocultar campos según tipo de consulta
  if (tipoConsulta === "cotizacion") {
    // Para cotizaciones, mostrar campos de presupuesto y plazo
    if (presupuestoField) {
      presupuestoField.closest('.form-group').style.display = "block";
    }
    if (plazoField) {
      plazoField.closest('.form-group').style.display = "block";
    }
  } else {
    // Para otros tipos, ocultar estos campos
    if (presupuestoField) {
      presupuestoField.closest('.form-group').style.display = "none";
      presupuestoField.value = "";
    }
    if (plazoField) {
      plazoField.closest('.form-group').style.display = "none";
      plazoField.value = "";
    }
  }
  
  // Cambiar placeholder del mensaje según tipo
  const mensajeField = document.getElementById("mensaje");
  if (mensajeField) {
    switch (tipoConsulta) {
      case "cotizacion":
        mensajeField.placeholder = "Describe tu proyecto, necesidades específicas, presupuesto aproximado...";
        break;
      case "consulta":
        mensajeField.placeholder = "Describe tu consulta o pregunta...";
        break;
      case "felicitacion":
        mensajeField.placeholder = "Comparte tu experiencia positiva con nosotros...";
        break;
      case "reclamo":
        mensajeField.placeholder = "Describe el problema o situación que necesitas resolver...";
        break;
      case "sugerencia":
        mensajeField.placeholder = "Comparte tu sugerencia para mejorar nuestros servicios...";
        break;
      case "trabajo":
        mensajeField.placeholder = "Describe la oportunidad laboral o colaboración que propones...";
        break;
      default:
        mensajeField.placeholder = "Describe tu consulta, solicitud o comentario...";
    }
  }
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
  
  // Validación específica para asunto
  if (field.name === "asunto" && value) {
    if (value.length < 5) {
      showFieldError(field, "El asunto debe tener al menos 5 caracteres");
      return false;
    }
  }
  
  // Validación específica para mensaje
  if (field.name === "mensaje" && value) {
    if (value.length < 10) {
      showFieldError(field, "El mensaje debe tener al menos 10 caracteres");
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
  const form = document.getElementById("contactoForm");
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
  
  // Validación de preferencias de contacto
  const preferenciasCheckboxes = document.querySelectorAll('input[name="preferencias[]"]');
  preferenciasCheckboxes.forEach(checkbox => {
    checkbox.addEventListener("change", function() {
      const checkedBoxes = document.querySelectorAll('input[name="preferencias[]"]:checked');
      if (checkedBoxes.length === 0) {
        // Si no hay ninguna preferencia seleccionada, marcar email por defecto
        document.getElementById("contacto-email").checked = true;
      }
    });
  });
  
  // Validación de newsletter
  const newsletterCheckbox = document.getElementById("newsletter");
  if (newsletterCheckbox) {
    newsletterCheckbox.addEventListener("change", function() {
      if (this.checked) {
        // Mostrar mensaje informativo sobre el newsletter
        showMessage("¡Gracias por suscribirte! Te enviaremos información sobre nuestros proyectos y novedades.", "success");
      }
    });
  }
});

// Función para enviar datos al servidor
function sendContactoData(formData) {
  return fetch('../api/contactos.php', {
    method: 'POST',
    body: formData,
    headers: {
      // No establecer Content-Type, el navegador lo hará automáticamente con boundary para FormData
    }
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
  })
  .then(data => {
    return data;
  })
  .catch(error => {
    console.error('Error en sendContactoData:', error);
    throw error;
  });
}
