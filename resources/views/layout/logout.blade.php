<div class="topbar">

    
    <div style="display:flex; align-items:center; gap:10px;">
        <i class="fas fa-user-shield" style="color:var(--primary);"></i>
        <span>{{ auth()->user()->telegram_username ?? auth()->user()->name ?? 'User' }}</span>
    </div>
    <form action="/logout" method="GET" style="margin:0;">
        <button type="submit" class="logout">Đăng Xuất</button>
    </form>
</div>