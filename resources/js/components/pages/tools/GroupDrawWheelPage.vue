<template>
  <div 
    class="wheel-page" 
    :style="{ backgroundImage: `linear-gradient(rgba(0,0,0,0.18), rgba(0,0,0,0.32)), url(${authBg})` }"
  >
    <!-- TOP NAVBAR -->
    <header class="navbar">
      <div class="nav-left">
        <button 
          type="button" 
          class="nav-back-btn" 
          @click="handleBack" 
          :title="isDrawScreen ? 'Quay lại tạo đội' : 'Về trang chủ'"
        >
          ‹
        </button>
        <div class="nav-title-wrap">
          <h1 class="nav-title">Bốc thăm chia bảng</h1>
          <span class="nav-sub" v-if="isDrawScreen">
            {{ numGroups }} bảng • {{ teams.length }} đội
          </span>
        </div>
      </div>
      <div class="nav-right" v-if="isDrawScreen">
        <button class="nav-btn-setup" @click="backToSetup">
          Cài đặt lại
        </button>
      </div>
    </header>

    <!-- MAIN BODY -->
    <main class="main-body">
      <!-- SETUP SCREEN: CENTERED CARD OVER SPLASH BG -->
      <div class="setup-wrapper" v-if="!isDrawScreen">
        <div class="setup-card">
          <div class="setup-header">
            <h2 class="setup-title">Cấu hình chia bảng</h2>
            <p class="setup-subtitle">Chọn số lượng bảng đấu và nhập danh sách các đội tham gia</p>
          </div>

          <div class="cfg-grid">
            <div class="mini-box">
              <label class="mini-label">Số bảng đấu</label>
              <select v-model="numGroups" class="mini-select">
                <option :value="2">2 bảng (A, B)</option>
                <option :value="3">3 bảng (A, B, C)</option>
                <option :value="4">4 bảng (A, B, C, D)</option>
                <option :value="5">5 bảng (A, B, C, D, E)</option>
                <option :value="6">6 bảng (A - F)</option>
                <option :value="8">8 bảng (A - H)</option>
              </select>
            </div>
            <div class="mini-box">
              <label class="mini-label">Tổng số đội đã nhập</label>
              <div class="team-count-val">{{ teams.length }} đội</div>
            </div>
          </div>
          
          <label class="field-label">Thêm đội tham gia</label>
          <div class="add-row">
            <input 
              type="text" 
              v-model="teamInput" 
              placeholder="Nhập tên đội..." 
              @keydown.enter="addTeam"
              class="team-input"
            >
            <button class="btn-add" @click="addTeam" title="Thêm">+</button>
          </div>
          
          <div class="seed-hint">
            Mỗi bảng chỉ nhận tối đa 1 đội hạt giống. Số lượng hạt giống không vượt quá số bảng đấu.
          </div>
          
          <div class="teams-list-scroll">
            <div v-if="teams.length === 0" class="empty-teams-hint">
              Chưa có đội nào. Hãy nhập tên đội ở trên hoặc bấm "Điền mẫu".
            </div>
            <div v-for="t in teams" :key="t.name" class="team-item">
              <button 
                :class="['seed-toggle-btn', { on: t.seed }]" 
                @click="toggleSeed(t.name)"
                title="Bật/tắt hạt giống"
              >
                {{ t.seed ? 'Hạt giống' : 'Đặt hạt giống' }}
              </button>
              <span class="team-name-text">{{ t.name }}</span>
              <button class="btn-del-team" @click="removeTeam(t.name)" title="Xóa">×</button>
            </div>
          </div>
          
          <div class="quick-actions">
            <button class="btn-secondary" @click="quickFill">
              Điền mẫu {{ Math.max(numGroups * 2, 8) }} đội
            </button>
            <button class="btn-secondary" @click="clearAll" v-if="teams.length > 0">
              Xóa tất cả
            </button>
          </div>
          
          <div :class="['status-info-box', infoStatus.class]">
            {{ infoStatus.text }}
          </div>
          
          <button 
            class="btn-start-draw" 
            :disabled="!canStart" 
            @click="startDraw"
          >
            Bắt đầu bốc thăm
          </button>
        </div>
      </div>

      <!-- DRAW SCREEN: FULL WIDTH 1 SCREEN RESPONSIVE -->
      <div class="draw-stage" v-else>
        <!-- UPPER HALF: Wheel, Current Target, Spin Button -->
        <div class="stage-upper">
          <div class="phase-meta" v-if="!(seedPool.length === 0 && remaining.length === 0)">
            <span :class="['phase-badge', drawPhase === 'seed' ? 'seed' : 'normal']">
              {{ drawPhase === 'seed' ? 'QUAY HẠT GIỐNG' : 'QUAY CÁC ĐỘI' }}
            </span>
            <span class="progress-badge">{{ progressText }}</span>
          </div>
          
          <div class="wheel-wrapper">
            <div class="wheel-box" v-show="activePool.length > 0">
              <div class="wheel-pointer"></div>
              <div 
                class="wheel-disc" 
                :style="{ transform: `rotate(${rot}deg)`, transition: wheelTransition }"
              >
                <svg viewBox="0 0 100 100">
                  <template v-if="activePool.length === 1">
                    <circle cx="50" cy="50" r="50" :fill="activeColors[0]" />
                    <text x="50" y="20" fill="#fff" font-size="6" font-weight="800" text-anchor="middle" dominant-baseline="middle">
                      {{ truncate(activePool[0], 9) }}
                    </text>
                  </template>
                  <template v-else-if="activePool.length > 1">
                    <g v-for="(p, i) in activePool" :key="p">
                      <path :d="getPathDef(i, activePool.length)" :fill="activeColors[i % activeColors.length]" />
                      <text 
                        :x="getTextX(i, activePool.length)" 
                        :y="getTextY(i, activePool.length)" 
                        fill="#fff" 
                        :font-size="activePool.length > 10 ? 3.2 : (activePool.length > 6 ? 3.8 : 4.4)" 
                        font-weight="800" 
                        text-anchor="middle" 
                        dominant-baseline="middle" 
                        :transform="getTextTransform(i, activePool.length)"
                      >
                        {{ truncate(p, 11) }}
                      </text>
                    </g>
                  </template>
                </svg>
              </div>
              <div class="wheel-hub">
                <span class="wheel-hub-dot"></span>
              </div>
            </div>

            <!-- Done Celebration -->
            <div class="celebration-box" v-if="seedPool.length === 0 && remaining.length === 0">
              <div class="celebration-title">Hoàn thành chia bảng</div>
              <p class="celebration-sub">Tất cả các đội đã được phân bổ đồng đều vào các bảng.</p>
              <button class="btn-draw-again" @click="startDraw">Bốc thăm lại</button>
            </div>
          </div>
          
          <div class="action-controls" v-if="!(seedPool.length === 0 && remaining.length === 0)">
            <div class="announcement-area">
              <div class="drawn-result" v-html="drawingNameHtml || '&nbsp;'"></div>
              <div 
                :class="['into-target', { seed: drawPhase === 'seed' }]" 
                v-html="intoHtml" 
              ></div>
            </div>
            
            <button 
              :class="['btn-spin-action', drawPhase === 'seed' ? 'seed' : 'normal']" 
              :disabled="spinning || activePool.length === 0" 
              @click="spin"
            >
              {{ drawPhase === 'seed' ? 'Quay hạt giống' : 'Bốc thăm' }}
            </button>
          </div>
        </div>
        
        <!-- LOWER HALF: 4 or 5 Groups arranged horizontally in 1 line -->
        <div class="stage-lower">
          <div class="groups-bar">
            <div class="groups-title-group">
              <span class="groups-main-title">Kết quả chia bảng</span>
              <span class="groups-chip">({{ groups.length }} bảng)</span>
            </div>
            <button class="btn-reset-groups" @click="startDraw" v-if="groups.length > 0">
              Bốc lại
            </button>
          </div>
          
          <!-- All groups in 1 line -->
          <div 
            class="groups-grid" 
            :style="{ '--col-count': groups.length }"
          >
            <div 
              v-for="(g, gi) in groups" 
              :key="gi" 
              :class="['group-card', { 'target-highlight': nextTargetIndex === gi && spinning }]"
              :style="{ '--accent-col': GROUP_COLORS[gi % GROUP_COLORS.length] }"
            >
              <div class="group-card-hdr">
                <span 
                  class="group-letter-badge" 
                  :style="{ backgroundColor: GROUP_COLORS[gi % GROUP_COLORS.length] }"
                >
                  {{ g.letter }}
                </span>
                <span class="group-title-name">Bảng {{ g.letter }}</span>
                <span class="group-slots-count">{{ g.teams.length }}/{{ slotsPerGroup }}</span>
              </div>
              
              <div class="slots-container">
                <div 
                  v-for="i in slotsPerGroup" 
                  :key="i" 
                  :class="['slot-row', g.teams[i-1] ? 'filled' : 'empty', g.teams[i-1]?.seed ? 'seed' : '']"
                >
                  <span class="slot-idx">{{ i }}</span>
                  <template v-if="g.teams[i-1]">
                    <span class="slot-team-name" :title="g.teams[i-1].name">{{ g.teams[i-1].name }}</span>
                    <span v-if="g.teams[i-1].seed" class="badge-seed">HG</span>
                  </template>
                  <template v-else>
                    <span class="slot-team-empty">— trống —</span>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import authBg from '@/assets/images/splash-bg.png';
import confetti from 'canvas-confetti';
import { initTheme } from '@/utils/theme.js';

const router = useRouter();

const COLORS = ['#E8192C','#2563EB','#0E9F6E','#D97706','#8B5CF6','#EC4899','#0891B2','#EA580C','#65A30D','#DB2777','#4F46E5','#0D9488','#CA8A04','#7C3AED','#DC2626','#059669'];
const GLETTER = ['A','B','C','D','E','F','G','H'];
const GROUP_COLORS = ['#2563EB','#0E9F6E','#D97706','#8B5CF6','#E8192C','#0891B2','#DB2777','#CA8A04'];

const numGroups = ref(4);
const teamInput = ref('');
const teams = ref([]); // {name, seed}

const isDrawScreen = ref(false);
const groups = ref([]); 
const seedPool = ref([]);
const remaining = ref([]);
const drawPhase = ref('normal');
const spinning = ref(false);
const rot = ref(0);
const wheelTransition = ref('none');
const drawingNameHtml = ref('');

// Auto trim seeds if numGroups is decreased
watch(numGroups, (newVal) => {
  let seedsCount = 0;
  teams.value.forEach(t => {
    if (t.seed) {
      seedsCount++;
      if (seedsCount > newVal) {
        t.seed = false;
      }
    }
  });
});

const activePool = computed(() => drawPhase.value === 'seed' ? seedPool.value : remaining.value);
const activeColors = computed(() => COLORS);

const canStart = computed(() => teams.value.length >= numGroups.value * 2);

const nextTargetIndex = computed(() => {
  if (!isDrawScreen.value || activePool.value.length === 0) return -1;
  return getNextGroupIndex();
});

const infoStatus = computed(() => {
  const ng = numGroups.value;
  const len = teams.value.length;
  const seedsCount = teams.value.filter(t => t.seed).length;
  
  if (len < ng * 2) {
    return { class: '', text: `Cần tối thiểu ${ng * 2} đội cho ${ng} bảng (ít nhất 2 đội/bảng).` };
  }
  
  const per = len / ng;
  let msg = len % ng !== 0
    ? `${len} đội không chia đều ${ng} bảng — các bảng lệch ${Math.ceil(per)}/${Math.floor(per)} đội.`
    : `Đã nhập ${len} đội cho ${ng} bảng (mỗi bảng ${per} đội).`;
    
  if (seedsCount > 0) {
    msg += ` ${seedsCount} đội hạt giống sẽ phân bổ vào ${seedsCount} bảng riêng biệt.`;
  }
  
  return { 
    class: len % ng !== 0 ? 'warn' : 'ok', 
    text: msg 
  };
});

const slotsPerGroup = computed(() => {
  if (teams.value.length === 0 || groups.value.length === 0) return 0;
  return Math.ceil(teams.value.length / groups.value.length);
});

const progressText = computed(() => {
  if (!isDrawScreen.value) return '';
  const placed = groups.value.reduce((s, g) => s + g.teams.length, 0);
  if (drawPhase.value === 'seed') {
    return `Hạt giống: còn ${seedPool.value.length} đội chưa quay`;
  } else {
    return `Đã xếp ${placed}/${teams.value.length} đội`;
  }
});

const intoHtml = computed(() => {
  if (!isDrawScreen.value) return '';
  if (activePool.value.length > 0) {
    const gi = getNextGroupIndex();
    if (groups.value[gi]) {
      if (drawPhase.value === 'seed') {
        return `Hạt giống bốc được sẽ vào <b>Bảng ${groups.value[gi].letter}</b>`;
      } else {
        return `Đội bốc được sẽ vào <b>Bảng ${groups.value[gi].letter}</b>`;
      }
    }
  }
  return '';
});

function addTeam() {
  const v = teamInput.value.trim();
  if (!v) return;
  if (!teams.value.find(t => t.name === v)) {
    teams.value.push({ name: v, seed: false });
  }
  teamInput.value = '';
}

function removeTeam(name) {
  teams.value = teams.value.filter(t => t.name !== name);
}

function toggleSeed(name) {
  const t = teams.value.find(x => x.name === name);
  const ng = numGroups.value;
  const seedsCount = teams.value.filter(x => x.seed).length;
  
  if (!t.seed && seedsCount >= ng) {
    alert(`Tối đa ${ng} hạt giống (bằng số bảng ${ng}).`);
    return;
  }
  t.seed = !t.seed;
}

function quickFill() {
  const sampleNames = [
    'Team Rồng', 'Team Hổ', 'Team Báo', 'Team Sói',
    'Team Ưng', 'Team Gấu', 'Team Cáo', 'Team Nai',
    'Team Đại Bàng', 'Team Sư Tử', 'Team Cá Voi', 'Team Tê Giác',
    'Team Ngựa Vằn', 'Team Bò Tót', 'Team Sóc Bay', 'Team Hạc Trắng'
  ];
  const count = Math.max(numGroups.value * 2, 8);
  teams.value = sampleNames.slice(0, count).map((n, i) => ({
    name: n,
    seed: i < numGroups.value
  }));
}

function clearAll() {
  teams.value = [];
}

function handleBack() {
  if (isDrawScreen.value) {
    backToSetup();
  } else {
    router.push('/');
  }
}

function backToSetup() {
  isDrawScreen.value = false;
  spinning.value = false;
  if (window.history.state?.screen === 'draw') {
    window.history.back();
  }
}

function startDraw() {
  const ng = numGroups.value;
  groups.value = Array.from({ length: ng }, (_, i) => ({
    letter: GLETTER[i],
    teams: []
  }));
  
  seedPool.value = teams.value.filter(t => t.seed).map(t => t.name);
  remaining.value = teams.value.filter(t => !t.seed).map(t => t.name);
  drawPhase.value = seedPool.value.length > 0 ? 'seed' : 'normal';
  rot.value = 0;
  wheelTransition.value = 'none';
  drawingNameHtml.value = '';
  isDrawScreen.value = true;

  window.history.pushState({ screen: 'draw' }, '');
}

const onPopState = () => {
  if (isDrawScreen.value) {
    isDrawScreen.value = false;
    spinning.value = false;
  }
};

onMounted(() => {
  window.addEventListener('popstate', onPopState);
  document.documentElement.classList.remove('dark');
});

onUnmounted(() => {
  window.removeEventListener('popstate', onPopState);
  initTheme();
});

function getNextGroupIndex() {
  if (drawPhase.value === 'seed') {
    for (let i = 0; i < groups.value.length; i++) {
      if (!groups.value[i].teams.some(t => t.seed)) return i;
    }
  }
  
  let min = Infinity;
  let gi = 0;
  for (let i = 0; i < groups.value.length; i++) {
    if (groups.value[i].teams.length < min) {
      min = groups.value[i].teams.length;
      gi = i;
    }
  }
  return gi;
}

// SVG helpers
function getPathDef(i, n) {
  const cx = 50, cy = 50, r = 50;
  const a0 = (i / n) * 2 * Math.PI - Math.PI / 2;
  const a1 = ((i + 1) / n) * 2 * Math.PI - Math.PI / 2;
  const x0 = cx + r * Math.cos(a0);
  const y0 = cy + r * Math.sin(a0);
  const x1 = cx + r * Math.cos(a1);
  const y1 = cy + r * Math.sin(a1);
  const large = (a1 - a0) > Math.PI ? 1 : 0;
  return `M${cx},${cy} L${x0},${y0} A${r},${r} 0 ${large},1 ${x1},${y1} Z`;
}

function getMidAngle(i, n) {
  const a0 = (i / n) * 2 * Math.PI - Math.PI / 2;
  const a1 = ((i + 1) / n) * 2 * Math.PI - Math.PI / 2;
  return (a0 + a1) / 2;
}

function getTextX(i, n) {
  const am = getMidAngle(i, n);
  return 50 + 50 * 0.62 * Math.cos(am);
}

function getTextY(i, n) {
  const am = getMidAngle(i, n);
  return 50 + 50 * 0.62 * Math.sin(am);
}

function getTextTransform(i, n) {
  const am = getMidAngle(i, n);
  const tx = getTextX(i, n);
  const ty = getTextY(i, n);
  return `rotate(${am * 180 / Math.PI + 90} ${tx} ${ty})`;
}

function truncate(str, len) {
  return str.length > len + 1 ? str.slice(0, len) + '…' : str;
}

function spin() {
  if (spinning.value) return;
  const pool = activePool.value;
  if (pool.length === 0) return;
  
  spinning.value = true;
  
  const n = pool.length;
  const pick = Math.floor(Math.random() * n);
  const seg = 360 / n;
  const targetMid = pick * seg + seg / 2;
  const spins = 5;
  
  const add = 360 * spins - targetMid + (360 - (rot.value % 360));
  rot.value += add;
  
  wheelTransition.value = 'transform 4s cubic-bezier(.17,.67,.24,1)';
  
  setTimeout(() => {
    const isSeed = drawPhase.value === 'seed';
    const name = pool.splice(pick, 1)[0];
    const gi = getNextGroupIndex();
    
    groups.value[gi].teams.push({ name, seed: isSeed });
    drawingNameHtml.value = `<b style="color:${isSeed ? '#D97706' : '#DC2626'}">${name}</b> → Bảng ${groups.value[gi].letter}`;
    
    if (drawPhase.value === 'seed' && seedPool.value.length === 0) {
      drawPhase.value = 'normal';
    }
    
    if (seedPool.value.length === 0 && remaining.value.length === 0) {
      try {
        confetti({
          particleCount: 100,
          spread: 70,
          origin: { y: 0.6 }
        });
      } catch (e) {}
    }
    
    spinning.value = false;
  }, 4100);
}
</script>

<style scoped>
.wheel-page {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  min-height: 100vh;
  height: 100vh;
  width: 100vw;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-sizing: border-box;
}

/* TOP NAVBAR */
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 18px;
  background: rgba(0, 0, 0, 0.25);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
  flex-shrink: 0;
  z-index: 20;
}

.nav-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.nav-back-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s;
  backdrop-filter: blur(8px);
}
.nav-back-btn:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: scale(1.05);
}

.nav-title-wrap {
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.nav-title {
  font-size: 18px;
  font-weight: 800;
  color: #fff;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0,0,0,0.4);
}

.nav-sub {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.85);
  font-weight: 600;
}

.nav-btn-setup {
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 10px;
  padding: 6px 14px;
  cursor: pointer;
  transition: all 0.15s;
  backdrop-filter: blur(8px);
}
.nav-btn-setup:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: translateY(-1px);
}

/* MAIN BODY */
.main-body {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* SETUP SCREEN */
.setup-wrapper {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  overflow-y: auto;
}

.setup-card {
  width: 100%;
  max-width: 540px;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.8);
}

.setup-header {
  margin-bottom: 16px;
  text-align: center;
}

.setup-title {
  font-size: 20px;
  font-weight: 800;
  color: #111827;
  margin: 0 0 4px;
}

.setup-subtitle {
  font-size: 13px;
  color: #6B7280;
  margin: 0;
}

.cfg-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}

.mini-box {
  background: #F3F4F6 !important;
  border-radius: 12px;
  padding: 10px 14px;
}
.mini-label {
  display: block;
  font-size: 12px;
  color: #6B7280 !important;
  font-weight: 600;
  margin-bottom: 6px;
}
.mini-select {
  width: 100%;
  border: 1px solid #E5E7EB !important;
  background-color: #ffffff !important;
  border-radius: 8px;
  padding: 8px 10px;
  font-size: 14px;
  font-weight: 700;
  color: #1A1A1A !important;
  outline: none;
}
.team-count-val {
  padding: 6px 0;
  font-size: 15px;
  font-weight: 800;
  color: #111827 !important;
}

.field-label {
  display: block;
  font-size: 14px;
  font-weight: 700;
  color: #1F2937 !important;
  margin-bottom: 8px;
}

.add-row {
  display: flex;
  gap: 8px;
}
.team-input {
  flex: 1;
  background-color: #F3F4F6 !important;
  color: #111827 !important;
  border: 1.5px solid transparent !important;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px;
  outline: none;
  transition: all 0.15s;
}
.team-input::placeholder {
  color: #9CA3AF !important;
}
.team-input:focus {
  border-color: #E8192C !important;
  background-color: #ffffff !important;
  color: #111827 !important;
}
.btn-add {
  width: 46px;
  border: none;
  border-radius: 10px;
  background: #E8192C;
  color: #fff;
  font-size: 22px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s;
}
.btn-add:hover {
  background: #D72D36;
}

.seed-hint {
  font-size: 12px;
  color: #6B7280 !important;
  margin-top: 8px;
  line-height: 1.4;
}

.teams-list-scroll {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 12px;
  max-height: 200px;
  overflow-y: auto;
  padding-right: 4px;
}
.empty-teams-hint {
  font-size: 13px;
  color: #9CA3AF !important;
  text-align: center;
  padding: 16px;
  background-color: #F9FAFB !important;
  border-radius: 10px;
  border: 1px dashed #E5E7EB !important;
}
.team-item {
  display: flex;
  align-items: center;
  gap: 10px;
  background-color: #F3F4F6 !important;
  border-radius: 10px;
  padding: 8px 12px;
}
.seed-toggle-btn {
  font-size: 11px;
  font-weight: 700;
  border: 1px solid #E5E7EB !important;
  background-color: #ffffff !important;
  color: #6B7280 !important;
  border-radius: 6px;
  padding: 4px 8px;
  cursor: pointer;
  transition: all 0.2s;
}
.seed-toggle-btn.on {
  border-color: #D97706 !important;
  background-color: #FEF6E7 !important;
  color: #D97706 !important;
}
.team-name-text {
  flex: 1;
  font-size: 13px;
  font-weight: 700;
  color: #1F2937 !important;
}
.btn-del-team {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background-color: #ffffff !important;
  border: none;
  color: #9CA3AF !important;
  cursor: pointer;
  font-size: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-del-team:hover {
  color: #EF4444 !important;
}

.quick-actions {
  display: flex;
  gap: 8px;
  margin-top: 14px;
}
.btn-secondary {
  font-size: 12px;
  font-weight: 700;
  color: #4B5563 !important;
  background-color: #F3F4F6 !important;
  border: 1px solid #E5E7EB !important;
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-secondary:hover {
  background-color: #E5E7EB !important;
  color: #111827 !important;
}

.status-info-box {
  font-size: 12px;
  margin-top: 14px;
  padding: 10px 14px;
  border-radius: 10px;
  line-height: 1.4;
  background-color: #F3F4F6 !important;
  color: #6B7280 !important;
}
.status-info-box.warn {
  background-color: #FEF6E7 !important;
  color: #D97706 !important;
  font-weight: 600;
}
.status-info-box.ok {
  background-color: #E7F6EF !important;
  color: #0E9F6E !important;
  font-weight: 600;
}

.btn-start-draw {
  width: 100%;
  margin-top: 16px;
  background: #E8192C;
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 14px;
  font-size: 15px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(232, 25, 44, 0.35);
  transition: transform 0.1s;
}
.btn-start-draw:active:not(:disabled) {
  transform: scale(0.98);
}
.btn-start-draw:disabled {
  background: #E5B5BA;
  box-shadow: none;
  cursor: not-allowed;
}

/* DRAW SCREEN STAGE: 1 SCREEN FULL WIDTH RESPONSIVE */
.draw-stage {
  max-width: 1600px;
  width: 100%;
  margin: 0 auto;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  padding: 12px 18px 16px;
  gap: 12px;
}

/* UPPER HALF: Wheel & Action Controls (Centered with flex: 1) */
.stage-upper {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.phase-meta {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 22px;
}
.phase-badge {
  font-size: 13px;
  font-weight: 800;
  padding: 6px 18px;
  border-radius: 9999px;
  color: #fff;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}
.phase-badge.seed { background: #D97706; }
.phase-badge.normal { background: #E8192C; }

.progress-badge {
  font-size: 16px;
  color: #fff;
  font-weight: 700;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}

.wheel-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
}

.wheel-box {
  position: relative;
  width: clamp(270px, 40vh, 420px);
  height: clamp(270px, 40vh, 420px);
  margin: 0 0 16px;
}

.wheel-pointer {
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
  width: 0;
  height: 0;
  border-left: 15px solid transparent;
  border-right: 15px solid transparent;
  border-top: 26px solid #E8192C;
  filter: drop-shadow(0 3px 6px rgba(0, 0, 0, .45));
}

.wheel-disc {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  position: relative;
  box-shadow: 0 14px 40px rgba(0, 0, 0, .45), 0 0 0 5px rgba(255, 255, 255, 0.95);
  border: 4px solid #fff;
}
.wheel-disc svg {
  width: 100%;
  height: 100%;
  display: block;
  border-radius: 50%;
}

.wheel-hub {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: clamp(42px, 5.5vh, 58px);
  height: clamp(42px, 5.5vh, 58px);
  background: #fff;
  border-radius: 50%;
  box-shadow: 0 4px 14px rgba(0, 0, 0, .3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 5;
}
.wheel-hub-dot {
  width: 16px;
  height: 16px;
  background: #DC2626;
  border-radius: 50%;
}

.celebration-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 32px;
  text-align: center;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  border-radius: 18px;
  box-shadow: 0 12px 36px rgba(0, 0, 0, 0.35);
}
.celebration-title { font-size: 18px; font-weight: 800; color: #0E9F6E; }
.celebration-sub { font-size: 13px; color: #4B5563; margin: 4px 0 12px; }
.btn-draw-again {
  font-size: 14px;
  font-weight: 700;
  color: #E8192C;
  background: #FDECEE;
  border: 1px solid #F8B4B4;
  border-radius: 8px;
  padding: 8px 18px;
  cursor: pointer;
}

.action-controls {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.announcement-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 42px;
}

.drawn-result {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.95);
  min-height: 20px;
  text-align: center;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
}
.drawn-result :deep(b) {
  font-weight: 800;
  font-size: 16px;
  background: #fff;
  padding: 2px 10px;
  border-radius: 6px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

.into-target {
  font-size: 15px;
  color: #fff;
  font-weight: 700;
  min-height: 20px;
  text-align: center;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}
.into-target :deep(b) {
  color: #FDE047;
  font-weight: 900;
  font-size: 17px;
}
.into-target.seed :deep(b) {
  color: #FBBF24;
}

.btn-spin-action {
  min-width: 250px;
  max-width: 320px;
  color: #fff;
  border: none;
  border-radius: 14px;
  padding: 12px 32px;
  font-size: 16px;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.1s, box-shadow 0.15s;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
  letter-spacing: 0.3px;
}
.btn-spin-action:active:not(:disabled) {
  transform: scale(0.97);
}
.btn-spin-action.seed {
  background: #D97706;
}
.btn-spin-action.normal {
  background: #E8192C;
}
.btn-spin-action:disabled {
  background: rgba(255, 255, 255, 0.4);
  color: rgba(255, 255, 255, 0.7);
  box-shadow: none;
  cursor: default;
}

/* LOWER HALF: GROUPS RESULT IN 1 HORIZONTAL ROW */
.stage-lower {
  width: 100%;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
}

.groups-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.groups-title-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.groups-main-title {
  font-size: 14px;
  font-weight: 800;
  color: #fff;
  text-shadow: 0 1px 4px rgba(0,0,0,0.5);
}

.groups-chip {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.8);
  font-weight: 600;
}

.btn-reset-groups {
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  cursor: pointer;
  padding: 4px 10px;
  border-radius: 8px;
  backdrop-filter: blur(8px);
  transition: all 0.15s;
}
.btn-reset-groups:hover {
  background: rgba(255, 255, 255, 0.35);
}

/* GRID WITH REPEAT(N, 1fr) FOR 1 ROW */
.groups-grid {
  display: grid;
  gap: 10px;
  width: 100%;
  grid-template-columns: repeat(var(--col-count, 4), minmax(0, 1fr));
}

@media (max-width: 860px) {
  .groups-grid {
    display: flex;
    overflow-x: auto;
    padding-bottom: 6px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
  }
  .group-card {
    min-width: 210px;
    flex: 1 0 210px;
    scroll-snap-align: start;
  }
}

.group-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1.5px solid rgba(255, 255, 255, 0.8);
  border-radius: 12px;
  padding: 8px 10px;
  display: flex;
  flex-direction: column;
  min-width: 0;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  transition: all 0.25s ease;
}

.group-card.target-highlight {
  border-color: var(--accent-col, #E8192C);
  box-shadow: 0 0 0 2px var(--accent-col, #E8192C), 0 8px 20px rgba(0,0,0,.25);
  animation: pulse-card 1s infinite alternate;
}

@keyframes pulse-card {
  from { transform: scale(1); }
  to { transform: scale(1.02); }
}

.group-card-hdr {
  font-size: 13px;
  font-weight: 800;
  color: #1E293B;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.group-letter-badge {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.group-title-name {
  font-weight: 800;
}

.group-slots-count {
  font-size: 11px;
  color: #94A3B8;
  font-weight: 600;
  margin-left: auto;
}

.slots-container {
  display: flex;
  flex-direction: column;
  gap: 4px;
  max-height: clamp(90px, 16vh, 160px);
  overflow-y: auto;
  padding-right: 2px;
}

.slot-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 8px;
  border-radius: 7px;
  background: #fff;
  border: 1px solid #E5E7EB;
  min-height: 28px;
  font-size: 13px;
  box-sizing: border-box;
}

.slot-idx {
  width: 20px;
  height: 20px;
  border-radius: 5px;
  background: #F3F4F6;
  font-size: 10px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6B7280;
  flex-shrink: 0;
}

.slot-team-name {
  flex: 1;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #1E293B;
}

.slot-team-empty {
  color: #9CA3AF;
  font-style: italic;
  font-weight: 400;
  font-size: 12px;
}

.slot-row.filled {
  animation: pop-slot .35s ease;
}

.slot-row.seed {
  background: #FFFBEB;
  border-color: #FDE68A;
}
.slot-row.seed .slot-team-name {
  color: #B45309;
  font-weight: 800;
}
.slot-row.seed .slot-idx {
  background: #F59E0B;
  color: #fff;
}

.badge-seed {
  font-size: 9px;
  font-weight: 800;
  color: #fff;
  background: #D97706;
  padding: 2px 5px;
  border-radius: 4px;
  flex-shrink: 0;
}

@keyframes pop-slot {
  from { opacity: 0; transform: scale(.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>
