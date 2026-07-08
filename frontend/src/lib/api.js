import axios from 'axios'
export const api = axios.create({
  baseURL: '/api',
  headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Inertia': 'true' }
})
export default api
