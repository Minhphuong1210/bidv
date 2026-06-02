<ul class="menu">
    <li class="menu-item @if($tab == 'tab-create') active @endif">
        <a href="/vacreate" style="text-decoration:none; color:inherit; display:block; width:100%;">
            <i class="fas fa-magic" style="margin-right:10px;"></i> TẠO BÁNH
        </a>
    </li>
    <li class="menu-item @if($tab == 'tab-profile') active @endif">
        <a href="/profile" style="text-decoration:none; color:inherit; display:block; width:100%;">
            <i class="fas fa-user-circle" style="margin-right:10px;"></i> HỒ SƠ
        </a>
    </li>
    <li class="menu-item @if($tab == 'tab-withdraw') active @endif">
        <a href="/withdraw" style="text-decoration:none; color:inherit; display:block; width:100%;">
            <i class="fas fa-wallet" style="margin-right:10px;"></i> RÚT TIỀN
        </a>
    </li>
    <li>
        <a href="#"><i class="fas fa-headset" style="margin-right:10px;"></i> HỖ TRỢ</a>
    </li>
    <li>
        <a href="#"><i class="fab fa-telegram" style="margin-right:10px;"></i> TELEGRAM</a>
    </li>
</ul>