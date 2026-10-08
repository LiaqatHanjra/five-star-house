export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

export async function api(path, { method = 'GET', body, isForm = false } = {}) {
  const headers = { Accept: 'application/json' }
  const token = localStorage.getItem('admin_token')
  if (token) headers.Authorization = `Bearer ${token}`

  const options = { method, headers }
  if (body) {
    if (isForm) {
      options.body = body
    } else {
      headers['Content-Type'] = 'application/json'
      options.body = JSON.stringify(body)
    }
  }

  const response = await fetch(path, options)
  const data = await response.json().catch(() => ({}))

  if (response.status === 401 && !path.endsWith('/login')) {
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_user')
    if (!window.location.pathname.startsWith('/admin/login')) {
      window.location.assign('/admin/login')
    }
  }

  if (!response.ok) {
    const first = Object.values(data.errors || {})[0]
    const message = Array.isArray(first) ? first[0] : (data.message || 'Request failed')
    throw new ApiError(message, response.status, data.errors || {})
  }

  return data
}
