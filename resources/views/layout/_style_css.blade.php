<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

:root {
    --primary: #4facfe;
    --primary-dark: #0088cc;
    --accent: #00f2fe;
    --bg-main: #f0f4f8;
    --sidebar-bg: rgba(255, 255, 255, 0.8);
    --card-bg: rgba(255, 255, 255, 0.95);
    --text-main: #1e293b;
    --text-muted: #64748b;
    --border-color: rgba(226, 232, 240, 0.8);
    --glass-border: rgba(255, 255, 255, 0.5);
    --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

body {
    background: var(--bg-main);
    color: var(--text-main);
    overflow-x: hidden;
}

.container {
    display: flex;
    min-height: 100vh;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 280px;
    background: var(--sidebar-bg);
    backdrop-filter: blur(12px);
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    height: 100vh;
    z-index: 100;
}

.logo {
    padding: 30px;
    font-size: 24px;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

.user {
    padding: 20px 30px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 20px;
}

.user img {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    border: 2px solid var(--primary);
    padding: 2px;
    background: #fff;
}

.user p {
    font-weight: 600;
    font-size: 15px;
    color: var(--text-main);
}

.menu {
    list-style: none;
    padding: 0 15px;
    flex: 1;
}

.menu li {
    margin-bottom: 8px;
    border-radius: 10px;
    overflow: hidden;
    transition: var(--transition);
}

.menu li a, .menu li.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: var(--transition);
}

.menu li:hover {
    background: rgba(79, 172, 254, 0.05);
}

.menu li:hover a, .menu li:hover.menu-item {
    color: var(--primary);
}

.menu li.active {
    background: linear-gradient(135deg, var(--primary), var(--accent));
}

.menu li.active a, .menu li.active.menu-item {
    color: #fff;
    font-weight: 600;
}

/* ===== MAIN CONTENT ===== */
.main {
    flex: 1;
    padding: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

/* ===== TOPBAR ===== */
.topbar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 20px;
    margin-bottom: 40px;
}

.topbar span {
    font-weight: 500;
    color: var(--text-muted);
}

.logout {
    background: #fff;
    color: #ef4444;
    border: 1px solid #fee2e2;
    padding: 8px 18px;
    cursor: pointer;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: var(--transition);
}

.logout:hover {
    background: #fef2f2;
    transform: translateY(-1px);
}

/* ===== CARDS & COMMON ===== */
.card {
    background: var(--card-bg);
    padding: 30px;
    border-radius: 20px;
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-md);
    margin-bottom: 30px;
}

h2 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 25px;
}

/* ===== INPUTS & BUTTONS ===== */
input, select {
    width: 100%;
    padding: 14px 18px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 15px;
    transition: var(--transition);
    background: #f8fafc;
    outline: none;
}

input:focus, select:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.1);
}

.btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 700;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
    transition: var(--transition);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
}

.btn:active {
    transform: translateY(0);
}

/* ===== HISTORY LIST ===== */
#historyList {
    display: grid;
    gap: 15px;
}

.item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 20px;
    border-radius: 15px;
    border: 1px solid var(--border-color);
    transition: var(--transition);
}

.item:hover {
    transform: translateX(5px);
    border-color: var(--primary);
    box-shadow: var(--shadow-sm);
}

.item b {
    color: var(--primary);
    font-size: 16px;
    display: block;
    margin-bottom: 4px;
}

.item span.money {
    color: #10b981;
    font-weight: 700;
    font-size: 14px;
}

.percent {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
}

/* ===== MOBILE ADAPTATION ===== */
@media (max-width: 991px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
    }

    .logo {
        padding: 20px;
    }

    .user {
        display: none;
    }

    .menu {
        display: flex;
        padding: 0 10px 10px;
        overflow-x: auto;
        gap: 8px;
    }

    .menu li {
        margin-bottom: 0;
        flex-shrink: 0;
    }

    .main {
        padding: 20px;
    }

    .topbar {
        display: none;
    }
}

    .bank-option {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        background: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .bank-option:hover {
        border-color: var(--primary);
        background: rgba(79, 172, 254, 0.05);
        transform: translateY(-3px);
    }

    .bank-option.active {
        border-color: var(--primary);
        background: linear-gradient(135deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
        box-shadow: 0 0 0 1px var(--primary);
    }

    .bank-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: #fff;
        padding: 25px;
        border-radius: 20px;
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .stat-card i {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 80px;
        opacity: 0.05;
        color: var(--text-main);
    }

    .stat-card h4 {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 10px;
    }

    .stat-card .value {
        font-size: 24px;
        font-weight: 800;
        color: var(--primary);
    }

    .stat-card.withdrawn .value {
        color: #f43f5e;
    }

    .stat-card.balance .value {
        color: #10b981;
    }

    /* Tabs transition */
    .tab-pane {
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* History Item Simplified */
    .history-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 15px 25px;
        border-radius: 16px;
        border: 1.5px solid var(--border-color);
        cursor: pointer;
        transition: var(--transition);
        margin-bottom: 12px;
    }

    .history-item:hover {
        border-color: var(--primary);
        background: rgba(79, 172, 254, 0.02);
        transform: translateX(8px);
    }

    .history-item b {
        font-size: 15px;
        color: var(--text-main);
    }

    .history-item .bank-name {
        font-size: 13px;
        color: var(--primary);
        font-weight: 600;
    }

    /* Detail Modal Specifics */
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px dashed #e2e8f0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-size: 13px;
        color: var(--text-muted);
    }

    .detail-value {
        font-weight: 700;
        color: var(--text-main);
        font-size: 15px;
    }

    .copy-btn {
        color: var(--primary);
        cursor: pointer;
        margin-left: 8px;
    }

    /* Bank List Cards (Profile) */
    .bank-card-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
    }

    .bank-item-card {
        background: #fff;
        padding: 15px 20px;
        border-radius: 18px;
        border: 1.5px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: var(--transition);
    }

    .bank-item-card:hover {
        border-color: var(--primary);
        box-shadow: var(--shadow-sm);
    }

    .bank-info h5 {
        margin: 0 0 5px 0;
        color: var(--primary);
        font-size: 16px;
        font-weight: 700;
    }

    .bank-info p {
        margin: 0;
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.5;
    }

    .bank-qr-preview {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: var(--transition);
    }

    .bank-qr-preview:hover {
        transform: scale(1.1);
    }

    .bank-actions {
        display: flex;
        gap: 8px;
        margin-left: 15px;
    }

    .action-btn {
        /* width: 34px; */
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: var(--transition);
        border: 1.5px solid var(--border-color);
        background: #f8fafc;
    }

    .action-btn.edit {
        color: var(--primary);
    }

    .action-btn.edit:hover {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    .action-btn.delete {
        color: #ef4444;
    }

    .action-btn.delete:hover {
        background: #ef4444;
        color: #fff;
        border-color: #ef4444;
    }

    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .tab-btn {
        flex: 1;
        padding: 10px;
        border: none;
        cursor: pointer;
        background: #eee;
        border-radius: 8px;
        font-weight: 600;
    }
    
    .tab-btn.active {
        background: var(--primary);
        color: #fff;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    input, textarea, select {
        width: 100%;
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    
    textarea {
        height: 120px;
    }
    
    .btn {
        background: var(--primary);
        color: #fff;
        padding: 10px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    </style>