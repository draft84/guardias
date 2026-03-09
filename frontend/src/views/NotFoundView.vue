<template>
  <div class="not-found-view">
    <div class="error-content">
      <h1>404</h1>
      <h2>Página no encontrada</h2>
      <p>La página que buscas no existe.</p>
      <router-link to="/" class="btn-primary">← Volver al Inicio</router-link>
    </div>
  </div>
</template>

<style scoped>
.not-found-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface-ground);
}
.error-content {
  text-align: center;
}
.error-content h1 {
  font-size: 96px;
  margin: 0;
  color: var(--primary-color);
}
.error-content h2 {
  font-size: 24px;
  margin: 20px 0 10px 0;
  color: var(--text-color);
}
.error-content p {
  color: var(--text-color-secondary);
  margin-bottom: 30px;
}
.btn-primary {
  display: inline-block;
  background: var(--primary-color);
  color: white;
  text-decoration: none;
  padding: 12px 30px;
  border-radius: 6px;
  font-weight: 600;
}
</style>
