<template>
    <div class="w-full h-[300px] sm:h-[400px] md:h-[500px]">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </template>
  
  <script setup>
  import { Bar } from 'vue-chartjs'
  import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale
  } from 'chart.js'
  
  ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)
  
  const chartData = {
    labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5','Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
    datasets: [
      {
        label: 'Điểm phong độ',
        backgroundColor: '#3b82f6',
        data: [1.5, 2.0, 2.8, 3.5, 2.3, 3.8, 3.6, 2.0, 1.6, 4.0, 2.0, 2.5]
      }
    ]
  }
  
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top'
      },
      title: {
        display: true,
        text: 'Phong độ qua các tháng'
      }
    },
    scales: {
      y: {
        suggestedMin: 0,
        suggestedMax: 4
      }
    }
  }
  </script>
  