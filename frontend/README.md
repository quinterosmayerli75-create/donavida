# DonaVida – Frontend (React + TypeScript + Vite)

Panel del hospital y portal del donante. Consume la API de Laravel.

```bash
npm install
cp .env.example .env      # en Windows: copy .env.example .env
npm run dev               # abre http://localhost:5173
```

Estructura:

```
src/
├── api/cliente.ts        # Axios configurado con la URL de la API y el token
├── componentes/          # Botones, tarjetas, menú... compartidos por todos
├── modulos/
│   ├── autenticacion/    # Integrante A
│   ├── donantes/         # Integrante B
│   ├── hospitales/       # Integrante C
│   └── citas/            # Integrante D
├── paginas/Inicio.tsx    # Página de prueba de conexión con la API
├── App.tsx               # Rutas
└── main.tsx
```
