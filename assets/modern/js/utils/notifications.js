import Swal from 'sweetalert2'

/**
 * Modern Notification System
 * Replaces legacy alert/confirm with SweetAlert2
 */

const notifications = {
  success(message, title = 'Éxito') {
    Swal.fire({
      icon: 'success',
      title,
      text: message,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    })
  },

  error(message, title = 'Error') {
    Swal.fire({
      icon: 'error',
      title,
      text: message,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 5000,
      timerProgressBar: true
    })
  },

  warning(message, title = 'Advertencia') {
    Swal.fire({
      icon: 'warning',
      title,
      text: message,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 4000,
      timerProgressBar: true
    })
  },

  info(message, title = 'Información') {
    Swal.fire({
      icon: 'info',
      title,
      text: message,
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    })
  },

  async confirm(message, title = '¿Estás seguro?') {
    const result = await Swal.fire({
      icon: 'question',
      title,
      text: message,
      showCancelButton: true,
      confirmButtonColor: '#0ea5e9',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Sí, confirmar',
      cancelButtonText: 'Cancelar'
    })
    
    return result.isConfirmed
  },

  loading(message = 'Cargando...') {
    Swal.fire({
      title: message,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading()
      }
    })
  },

  close() {
    Swal.close()
  }
}

export default notifications
