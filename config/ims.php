<?php

return [
  'prefix' => [
      'out' => 'OUT',
      'in' => 'IN',
      'adjustment' => 'ADJ',
      'transfer' => 'TRF',
      'repair' => 'RPR'
  ],
  'approval' => [
      'allow_self_approval' => false,
      'levels' => [ // per tipe transaksi; default 1 level
          'out' => ['ims.approval.act'], 
          'in' => ['ims.approval.act'], 
          'adjustment' => ['ims.approval.act'],
          'transfer' => ['ims.approval.act'], 
          'repair_in' => ['ims.approval.act'],
      ],
      'repair_result_requires_approval' => true,
      'transfer_same_warehouse_requires_approval' => true,
  ],
  'min_description_length' => 10,
  'loan_overdue_notify_days' => [0, 3],
  'attachments' => [
      'disk' => 'local', 
      'max_kb' => 5120, 
      'mimes' => ['jpg','jpeg','png','pdf']
  ],
  'pagination' => 15,
];
