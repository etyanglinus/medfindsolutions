<li class="{{ isRoute('admin.habit.index', 'active') }}">
    <a class="nav-link" href="{{ route('admin.habit.index', ['code' => getSessionLanguage()]) }}">
        {{ __('Habits') }}
    </a>
</li>
