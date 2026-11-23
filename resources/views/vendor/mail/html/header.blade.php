@props(['url'])
<tr>
    <td class="header" style="padding: 30px 0 18px; text-align: center;">
        <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
            <span style="display: inline-block; box-sizing: border-box; width: 46px; height: 46px; border-radius: 13px; background-color: #7c3aed; background-image: linear-gradient(135deg, #6366f1 0%, #a855f7 55%, #d946ef 100%); color: #ffffff; font-family: 'Poppins', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 24px; font-weight: 700; line-height: 46px; text-align: center;">R</span>
            <span style="display: block; margin-top: 10px; color: #6d28d9; font-family: 'Poppins', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 700;">{{ config('app.name') }}</span>
            <span style="display: block; margin-top: 3px; color: #94a3b8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 0.24em; text-transform: uppercase;">A gathering to remember.</span>
        </a>
    </td>
</tr>
