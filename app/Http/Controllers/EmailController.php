<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Emaildata;
use App\Models\Customer;

class EmailController extends Controller
{
    // GET /emails — รายการอีเมล (ล่าสุดก่อน)
    public function index()
    {
        $emails = Emaildata::with('contacts')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        return response()->json($emails);
    }

    // GET /emails/{id} — รายละเอียดอีเมล
    public function show($id)
    {
        $email = Emaildata::with('contacts')->findOrFail($id);
        return response()->json($email);
    }

    // PUT /emails/{id} — แก้ไข subject/body
    public function update(Request $request, $id)
    {
        $email = Emaildata::findOrFail($id);
        $email->update($request->only(['subject', 'body']));
        return response()->json(['status' => 'success']);
    }

    // POST /emails/{id}/confirm — ยืนยันอีเมล (เช่น เปลี่ยน status)
    public function confirm($id)
    {
        $email = Emaildata::findOrFail($id);
        // เปลี่ยน status ใน pivot ทุกแถวที่ผูกกับ email นี้
        $email->contacts()->updateExistingPivot($email->contacts->pluck('id'), [
            'status' => 'sent'
        ]);
        return response()->json(['status' => 'success']);
    }
}
