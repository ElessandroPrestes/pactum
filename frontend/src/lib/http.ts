import axios, { type AxiosInstance } from 'axios'

const baseURL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1'

export const http: AxiosInstance = axios.create({
  baseURL,
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})
