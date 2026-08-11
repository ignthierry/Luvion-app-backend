<table style="width:100%; border-collapse:collapse; margin-bottom:10px;">
    <tr>
        <td style="width:110px; vertical-align:middle; text-align:center; border:none;">
            <img src="{{ public_path('images/logo-luvion.png') }}" style="width:95px; height:auto;" />
        </td>
        <td style="border:none; vertical-align:middle; padding-left:12px;">
            <div style="font-size:20px; font-weight:bold; color:#0B3D91; letter-spacing:0.5px;">{{ config('company.name') }}</div>
            <div style="font-size:11px; color:#333; margin-top:4px; line-height:1.5;">
                {{ config('company.address') }}<br/>
                NPWP: {{ config('company.npwp') }} &nbsp;|&nbsp; Telp: {{ config('company.phone') }}<br/>
                Email: {{ config('company.email') }}
            </div>
        </td>
    </tr>
</table>
<div style="border-top:3px solid #0B3D91; border-bottom:1px solid #0B3D91; height:3px; margin-bottom:14px;"></div>
