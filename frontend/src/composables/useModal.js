import { ref, onMounted, onBeforeUnmount } from 'vue';
import * as bootstrap from 'bootstrap';

/**
 * Composable para gestionar modales de Bootstrap 5 con Vue 3.
 * 
 * @param {string} modalId - El ID del elemento modal en el DOM.
 * @param {Object} options - Opciones adicionales.
 * @param {Function} options.onClose - Callback que se ejecuta al cerrar o esconder el modal.
 */
export function useModal(modalId, options = {}) {
  const modalInstance = ref(null);

  onMounted(() => {
    const modalEl = document.getElementById(modalId);
    if (modalEl) {
      modalInstance.value = new bootstrap.Modal(modalEl);

      // Evento de Bootstrap que se dispara cuando el modal termina de ocultarse
      modalEl.addEventListener('hidden.bs.modal', () => {
        if (options.onClose) {
          options.onClose();
        }
        // Limpieza adicional de foco por seguridad (evita error ARIA)
        if (document.activeElement && document.activeElement !== document.body) {
          document.activeElement.blur();
        }
      });
    }
  });

  onBeforeUnmount(() => {
    // Es importante destruir la instancia para liberar memoria
    if (modalInstance.value) {
      modalInstance.value.dispose();
    }
  });

  const show = () => {
    modalInstance.value?.show();
  };

  const hide = () => {
    // Aplicar desenfoque antes de ocultar para evitar el error de aria-hidden
    if (document.activeElement && document.activeElement !== document.body) {
      document.activeElement.blur();
    }
    modalInstance.value?.hide();
  };

  return {
    instance: modalInstance,
    show,
    hide
  };
}
