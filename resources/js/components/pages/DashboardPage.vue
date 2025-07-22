<template>
    <div class="dashboard-page">
      <h1>🏆 Trang quản lý giải đấu (Dashboard)</h1>
  
      <section class="stats">
        <div class="stat-card">
          <h2>{{ tournaments.length }}</h2>
          <p>Giải đấu đã tạo</p>
        </div>
        <div class="stat-card">
          <h2>{{ players.length }}</h2>
          <p>Người chơi</p>
        </div>
      </section>
  
      <section class="recent">
        <h3>📋 Giải đấu gần đây</h3>
        <ul>
          <li v-for="tournament in tournaments.slice(0, 5)" :key="tournament.id">
            {{ tournament.name }} – {{ tournament.date }}
          </li>
        </ul>
      </section>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  
  // Fake data demo
  const tournaments = ref([])
  const players = ref([])
  
  onMounted(() => {
    // Giả lập gọi API - thay bằng real API từ Laravel sau này
    tournaments.value = [
      { id: 1, name: 'VPick Summer Cup', date: '2025-07-21' },
      { id: 2, name: 'VPick Pro League', date: '2025-07-18' },
      { id: 3, name: 'Hanoi Open', date: '2025-07-10' },
    ]
    players.value = [
      { id: 1, name: 'Nguyễn Văn A' },
      { id: 2, name: 'Trần Thị B' },
      { id: 3, name: 'Lê Văn C' },
    ]
  })
  </script>
  
  <style scoped>
  .dashboard-page {
    padding: 24px;
    font-family: system-ui;
  }
  
  .stats {
    display: flex;
    gap: 24px;
    margin-bottom: 32px;
  }
  
  .stat-card {
    flex: 1;
    background: #f0f4ff;
    padding: 16px;
    border-radius: 8px;
    text-align: center;
  }
  
  .stat-card h2 {
    font-size: 2rem;
    margin: 0;
  }
  
  .recent ul {
    padding-left: 20px;
  }
  
  .recent li {
    margin-bottom: 8px;
  }
  </style>
  