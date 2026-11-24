<?php
require_once __DIR__ . '/includes/auth.php';


/* AJAX handlers */
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    $bid    = (int)($_POST['booking_id']??0);
    $action = $_POST['action'];

    if ($action==='set_amounts') {
        $total = (float)($_POST['total_amount']??0);
        $adv   = (float)($_POST['advance_paid']??0);
        $pend  = max(0,$total-$adv);
        $stat  = $adv>=$total*0.99&&$total>0?'paid':($adv>0?'partial':'unpaid');
        try {
            $pdo->prepare('UPDATE bookings SET total_amount=?,advance_paid=?,pending_amount=?,payment_status=? WHERE id=?')
                ->execute([$total,$adv,$pend,$stat,$bid]);
            exit(json_encode(['success'=>true,'pending'=>$pend,'status'=>$stat]));
        } catch(PDOException $e){ exit(json_encode(['success'=>false,'error'=>$e->getMessage()])); }
    }

    if ($action==='verify_payment') {
        $approved = (int)($_POST['approved']??0);
        $note     = cl($_POST['note']??'');
        $amount   = (float)($_POST['amount']??0);
        try {
            $b=$pdo->prepare('SELECT total_amount,advance_paid FROM bookings WHERE id=?');
            $b->execute([$bid]); $bk=$b->fetch();
            if ($approved) {
                $newA=($bk['advance_paid']+$amount);
                $newP=max(0,$bk['total_amount']-$newA);
                $s=$newA>=$bk['total_amount']*0.99&&$bk['total_amount']>0?'paid':($newA>0?'partial':'unpaid');
                $pdo->prepare('UPDATE bookings SET advance_paid=?,pending_amount=?,payment_status=?,payment_verified=1,payment_note=? WHERE id=?')
                    ->execute([$newA,$newP,$s,$note,$bid]);
                $pdo->prepare('UPDATE payment_history SET verified=1,verified_by="admin",verified_at=NOW(),note=? WHERE booking_id=? ORDER BY created_at DESC LIMIT 1')
                    ->execute([$note,$bid]);
            } else {
                $pdo->prepare('UPDATE bookings SET payment_verified=0,payment_note=? WHERE id=?')->execute([$note,$bid]);
            }
            exit(json_encode(['success'=>true]));
        } catch(PDOException $e){ exit(json_encode(['success'=>false,'error'=>$e->getMessage()])); }
    }

    if ($action==='set_delivery') {
        $ds = cl($_POST['delivery_status']??'processing');
        try {
            $pdo->prepare('UPDATE bookings SET delivery_status=? WHERE id=?')->execute([$ds,$bid]);
            exit(json_encode(['success'=>true]));
        } catch(PDOException $e){ exit(json_encode(['success'=>false,'error'=>$e->getMessage()])); }
    }

    if ($action==='upload_delivery') {
        if (empty($_FILES['delivery_file'])) exit(json_encode(['success'=>false,'error'=>'No file.']));
        $f=$_FILES['delivery_file'];
        $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
        if (!in_array($ext,['zip','rar','7z'])) exit(json_encode(['success'=>false,'error'=>'ZIP/RAR/7Z only.']));
        if ($f['size']>500*1024*1024) exit(json_encode(['success'=>false,'error'=>'Max 500MB.']));
        $name='delivery_'.$bid.'_'.time().'.'.$ext;
        $dest=__DIR__.'/../uploads/client-work/'.$name;
        if (!move_uploaded_file($f['tmp_name'],$dest)) exit(json_encode(['success'=>false,'error'=>'Upload failed.']));
        $path='uploads/client-work/'.$name;
        try {
            $pdo->prepare('UPDATE bookings SET delivery_file=?,delivery_status="ready" WHERE id=?')->execute([$path,$bid]);
            exit(json_encode(['success'=>true,'file'=>$path]));
        } catch(PDOException $e){ exit(json_encode(['success'=>false,'error'=>$e->getMessage()])); }
    }
    if ($action==='history') {
        $bid=(int)($_POST['booking_id']??0);
        try {
            $h=$pdo->prepare('SELECT * FROM payment_history WHERE booking_id=? ORDER BY created_at DESC');
            $h->execute([$bid]);
            exit(json_encode(['success'=>true,'history'=>$h->fetchAll()]));
        } catch(PDOException $e){ exit(json_encode(['success'=>false,'history',[]])); }
    }
    exit(json_encode(['success'=>false,'error'=>'Unknown action.']));
}

/* Page query */
$filter=$_GET['filter']??'all';
$where=$filter==='unverified'?'WHERE b.payment_screenshot IS NOT NULL AND b.payment_verified=0'
      :($filter==='unpaid'   ?'WHERE b.payment_status="unpaid"'
      :($filter==='partial'  ?'WHERE b.payment_status="partial"'
      :($filter==='paid'     ?'WHERE b.payment_status="paid"' :'')));
try {
    $bookings=$pdo->query("SELECT b.*,u.name as uname,u.email as uemail FROM bookings b LEFT JOIN users u ON u.id=b.user_id $where ORDER BY b.created_at DESC LIMIT 100")->fetchAll();
    $st=$pdo->query("SELECT COUNT(*) as tot,SUM(total_amount) as ta,SUM(advance_paid) as ap,SUM(pending_amount) as pa,SUM(CASE WHEN payment_screenshot IS NOT NULL AND payment_verified=0 THEN 1 ELSE 0 END) as pv FROM bookings")->fetch();
} catch(Exception $e){ $bookings=[];$st=['tot'=>0,'ta'=>0,'ap'=>0,'pa'=>0,'pv'=>0]; }
$flash=getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Payments — Wings Cut Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="css/admin.css"/>
<style>
.ps-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.ps-card{background:#fff;border-radius:14px;padding:20px 16px;border:1px solid var(--border);text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.ps-card.red-card{background:var(--red);border-color:var(--red)}
.ps-card .psc-val{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--red);line-height:1;margin-bottom:4px}
.ps-card.red-card .psc-val{color:#fff}
.ps-card .psc-lbl{font-size:11px;color:#888;text-transform:uppercase;letter-spacing:1px;font-weight:600}
.ps-card.red-card .psc-lbl{color:rgba(255,255,255,.7)}
.ftabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.ftab{padding:7px 16px;border-radius:99px;font-size:12px;font-weight:600;border:1.5px solid var(--border);color:#888;text-decoration:none;transition:all .2s}
.ftab:hover,.ftab.active{background:var(--red);color:#fff;border-color:var(--red)}
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{padding:10px 12px;background:#fafafa;border-bottom:2px solid var(--border);text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#aaa}
.tbl td{padding:11px 12px;border-bottom:1px solid #f8f8f8;vertical-align:middle}
.tbl tr:hover td{background:#fefefe}
.bdg{display:inline-block;padding:2px 9px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.bdg-unpaid{background:#fee2e2;color:#991b1b}
.bdg-partial{background:#fef3c7;color:#92400e}
.bdg-paid{background:#dcfce7;color:#166534}
.bdg-processing{background:#e0f2fe;color:#0369a1}
.bdg-editing{background:#fef3c7;color:#92400e}
.bdg-ready{background:#dcfce7;color:#166534}
.bdg-delivered{background:#ede9fe;color:#5b21b6}
.bdg-verified{background:#dcfce7;color:#166534}
.bdg-pending{background:#fef3c7;color:#92400e}
.abtn{padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;border:none;font-family:inherit;background:rgba(139,0,0,.09);color:var(--red);transition:all .2s}
.abtn:hover{background:var(--red);color:#fff}
/* Modal */
.modal-bg{position:fixed;inset:0;z-index:5000;background:rgba(0,0,0,.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center}
.modal-box{background:#fff;border-radius:20px;width:min(720px,96vw);max-height:92vh;overflow-y:auto;box-shadow:0 28px 90px rgba(0,0,0,.28)}
.mb-head{position:sticky;top:0;z-index:2;background:#fff;padding:20px 26px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.mb-head h3{font-size:17px;font-weight:700;display:flex;align-items:center;gap:8px}
.mb-head h3 i{color:var(--red)}
.mb-close{width:32px;height:32px;border-radius:50%;background:#f5f5f5;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;color:#888}
.mb-close:hover{background:#fee2e2;color:#dc2626}
.mb-body{padding:22px 26px}
.mb-sec{margin-bottom:22px;padding-bottom:22px;border-bottom:1px solid #f5f5f5}
.mb-sec:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.mb-sec-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#bbb;margin-bottom:14px;display:flex;align-items:center;gap:6px}
.mb-sec-title i{color:var(--red)}
.sum-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.sum-box{background:#fafafa;border-radius:10px;padding:12px;text-align:center;border:1px solid var(--border)}
.sum-box strong{display:block;font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--red)}
.sum-box span{font-size:10px;color:#bbb;text-transform:uppercase;letter-spacing:1px}
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.fl label{display:block;font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.fl input,.fl select,.fl textarea{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;outline:none;font-family:inherit;transition:border-color .18s;background:#fafafa}
.fl input:focus,.fl select:focus,.fl textarea:focus{border-color:var(--red);background:#fff}
.sbtn{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;background:var(--red);color:#fff;border:none;border-radius:99px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .2s}
.sbtn:hover{background:#a30000;transform:translateY(-1px)}
.sbtn.out{background:none;color:var(--red);border:1.5px solid var(--red)}
.sbtn.out:hover{background:var(--red);color:#fff;transform:translateY(-1px)}
.sbtn.grn{background:#16a34a}
.sbtn.grn:hover{background:#15803d}
.sbtn.rd{background:#dc2626}
.sbtn.rd:hover{background:#b91c1c}
.msg{padding:10px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-top:10px;display:none}
.msg.ok{background:#dcfce7;color:#166534}
.msg.er{background:#fee2e2;color:#991b1b}
.ss-wrap{border-radius:10px;overflow:hidden;border:1px solid var(--border);margin-bottom:12px;background:#fafafa;max-height:280px;display:flex;align-items:center;justify-content:center}
.ss-wrap img{max-width:100%;max-height:280px;object-fit:contain;display:block}
.dz{border:2px dashed var(--border);border-radius:10px;padding:28px;text-align:center;cursor:pointer;transition:all .2s}
.dz:hover,.dz.on{border-color:var(--red);background:rgba(139,0,0,.03)}
.dz i{font-size:32px;color:#ddd;margin-bottom:10px;display:block}
.dz p{font-size:13px;color:#aaa;margin:0}
.hist-row{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#fafafa;border-radius:8px;margin-bottom:8px}
.hist-row .ha{font-weight:700;color:var(--red);font-size:14px}
.hist-row .hd{font-size:11px;color:#bbb}
@media(max-width:768px){
  .ps-grid{grid-template-columns:1fr 1fr}
  .fg2,.fg3,.sum-row{grid-template-columns:1fr}
  .tbl td,.tbl th{padding:8px 8px;font-size:12px}
}
</style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
<?php include 'includes/topbar.php'; ?>
<div class="admin-content">

<div class="page-header" style="margin-bottom:20px">
  <div>
    <h1 style="display:flex;align-items:center;gap:10px"><i class="fas fa-credit-card" style="color:var(--red)"></i>Payment &amp; Delivery</h1>
    <p style="color:#888;font-size:13px">Track payments, verify screenshots, manage client deliveries</p>
  </div>
</div>

<!-- Stats -->
<div class="ps-grid">
  <div class="ps-card"><div class="psc-val">₹<?=number_format($st['ta'],0)?></div><div class="psc-lbl">Total Billed</div></div>
  <div class="ps-card red-card"><div class="psc-val">₹<?=number_format($st['ap'],0)?></div><div class="psc-lbl">Collected</div></div>
  <div class="ps-card"><div class="psc-val">₹<?=number_format($st['pa'],0)?></div><div class="psc-lbl">Pending</div></div>
  <div class="ps-card"><div class="psc-val" style="color:<?=($st['pv']>0)?'#f59e0b':'var(--red)'?>">
    <?=$st['pv']?></div><div class="psc-lbl">Awaiting Verify</div></div>
</div>

<!-- Filters -->
<div class="ftabs">
  <a href="?filter=all"        class="ftab <?=$filter==='all'?'active':''?>">All</a>
  <a href="?filter=unverified" class="ftab <?=$filter==='unverified'?'active':''?>">
    Verify Pending<?php if($st['pv']>0):?> <span style="background:#ef4444;color:#fff;border-radius:99px;padding:0 6px;font-size:10px;margin-left:3px"><?=$st['pv']?></span><?php endif;?>
  </a>
  <a href="?filter=unpaid"  class="ftab <?=$filter==='unpaid' ?'active':''?>">Unpaid</a>
  <a href="?filter=partial" class="ftab <?=$filter==='partial'?'active':''?>">Partial</a>
  <a href="?filter=paid"    class="ftab <?=$filter==='paid'   ?'active':''?>">Paid</a>
</div>

<!-- Table -->
<div style="background:#fff;border-radius:14px;border:1px solid var(--border);overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.06)">
<div style="overflow-x:auto">
<table class="tbl">
<thead><tr>
  <th>#</th><th>Client</th><th>Package</th>
  <th>Total</th><th>Advance</th><th>Pending</th>
  <th>Status</th><th>Screenshot</th><th>Delivery</th><th>Action</th>
</tr></thead>
<tbody>
<?php foreach($bookings as $b):
  $client=$b['uname']?:$b['name']; $email=$b['uemail']?:$b['email'];
?>
<tr>
  <td><strong style="color:var(--red)">#<?=$b['id']?></strong><br><span style="font-size:11px;color:#bbb"><?=date('d M y',strtotime($b['booking_date']))?></span></td>
  <td><strong><?=cl($client)?></strong><br><span style="font-size:11px;color:#bbb"><?=cl($email)?></span></td>
  <td style="text-transform:capitalize;font-size:13px"><?=cl($b['package'])?></td>
  <td><strong>₹<?=number_format($b['total_amount'],0)?></strong></td>
  <td style="color:#16a34a;font-weight:600">₹<?=number_format($b['advance_paid'],0)?></td>
  <td style="color:<?=$b['pending_amount']>0?'#dc2626':'#16a34a'?>;font-weight:600">₹<?=number_format($b['pending_amount'],0)?></td>
  <td><span class="bdg bdg-<?=$b['payment_status']?:''?>"><?=$b['payment_status']?:'—'?></span></td>
  <td>
    <?php if($b['payment_screenshot']): ?>
      <span class="bdg <?=$b['payment_verified']?'bdg-verified':'bdg-pending'?>"><?=$b['payment_verified']?'Verified':'Pending'?></span>
    <?php else: echo '<span style="color:#ddd;font-size:12px">None</span>'; endif; ?>
  </td>
  <td><span class="bdg bdg-<?=$b['delivery_status']?:''?>"><?=$b['delivery_status']?:'—'?></span></td>
  <td><button class="abtn" data-b="<?=htmlspecialchars(json_encode($b),ENT_QUOTES)?>" onclick="openPanel(JSON.parse(this.dataset.b))"><i class="fas fa-cog"></i> Manage</button></td>
</tr>
<?php endforeach; ?>
<?php if(empty($bookings)): ?>
<tr><td colspan="10" style="text-align:center;padding:48px;color:#ccc"><i class="fas fa-receipt" style="font-size:36px;display:block;margin-bottom:12px"></i>No bookings found</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div>
</div>

<!-- Modal -->
<div class="modal-bg" id="modal" style="display:none" onclick="if(event.target===this)closePanel()">
<div class="modal-box">
  <div class="mb-head">
    <h3><i class="fas fa-sliders-h"></i>Manage Booking <span id="mBid" style="color:var(--red)"></span></h3>
    <button class="mb-close" onclick="closePanel()"><i class="fas fa-times"></i></button>
  </div>
  <div class="mb-body">

    <!-- Summary -->
    <div class="mb-sec">
      <div class="mb-sec-title"><i class="fas fa-chart-pie"></i>Payment Summary</div>
      <div class="sum-row">
        <div class="sum-box"><strong id="mTotal">₹0</strong><span>Total Amount</span></div>
        <div class="sum-box"><strong id="mAdv">₹0</strong><span>Advance Paid</span></div>
        <div class="sum-box"><strong id="mPend">₹0</strong><span>Pending</span></div>
      </div>
      <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;font-size:13px">
        Payment: <span id="mPayStat" class="bdg"></span>
        &nbsp; Delivery: <span id="mDelStat" class="bdg"></span>
      </div>
    </div>

    <!-- Set Amounts -->
    <div class="mb-sec">
      <div class="mb-sec-title"><i class="fas fa-rupee-sign"></i>Set Amounts</div>
      <div class="fg2" style="margin-bottom:10px">
        <div class="fl"><label>Total Amount (₹)</label><input type="number" id="mSetTotal" placeholder="0" min="0" oninput="calcPend()"/></div>
        <div class="fl"><label>Advance Received (₹)</label><input type="number" id="mSetAdv" placeholder="0" min="0" oninput="calcPend()"/></div>
      </div>
      <div id="mPendCalc" style="font-size:13px;color:#888;margin-bottom:12px"></div>
      <button class="sbtn" onclick="saveAmounts()"><i class="fas fa-save"></i>Save Amounts</button>
      <div class="msg" id="mAmtMsg"></div>
    </div>

    <!-- Screenshot Verify -->
    <div class="mb-sec">
      <div class="mb-sec-title"><i class="fas fa-image"></i>Payment Screenshot</div>
      <div id="mSSWrap"></div>
      <div id="mVerifyForm" style="display:none">
        <div class="fg2" style="margin-bottom:12px">
          <div class="fl"><label>Amount Received (₹)</label><input type="number" id="mVerAmt" placeholder="0" min="0"/></div>
          <div class="fl"><label>Note</label><input type="text" id="mVerNote" placeholder="e.g. Advance via UPI"/></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="sbtn grn" onclick="verifyPay(1)"><i class="fas fa-check"></i>Approve</button>
          <button class="sbtn rd"  onclick="verifyPay(0)"><i class="fas fa-times"></i>Reject</button>
        </div>
        <div class="msg" id="mVerMsg"></div>
      </div>
    </div>

    <!-- Delivery -->
    <div class="mb-sec">
      <div class="mb-sec-title"><i class="fas fa-cloud-upload-alt"></i>Client Delivery</div>
      <div class="fg2" style="margin-bottom:14px">
        <div class="fl">
          <label>Delivery Status</label>
          <select id="mDelSelect">
            <option value="processing">⚙️ Processing</option>
            <option value="editing">✂️ Editing</option>
            <option value="ready">✅ Ready for Download</option>
            <option value="delivered">📦 Delivered</option>
          </select>
        </div>
        <div class="fl" style="display:flex;align-items:flex-end">
          <button class="sbtn out" onclick="saveDelStatus()"><i class="fas fa-save"></i>Update Status</button>
        </div>
      </div>
      <div class="dz" id="mDZ" onclick="document.getElementById('mDelFile').click()"
           ondragover="event.preventDefault();this.classList.add('on')"
           ondragleave="this.classList.remove('on')"
           ondrop="dropFile(event)">
        <input type="file" id="mDelFile" accept=".zip,.rar,.7z" style="display:none" onchange="pickFile(this)"/>
        <i class="fas fa-cloud-upload-alt"></i>
        <p>Click or drag &amp; drop ZIP / RAR / 7Z here<br><span style="font-size:11px">Max 500 MB</span></p>
      </div>
      <div id="mDelFileName" style="font-size:13px;color:var(--red);margin-top:8px;display:none"></div>
      <button class="sbtn" id="mDelUpBtn" style="margin-top:10px;display:none" onclick="uploadDel()"><i class="fas fa-upload"></i>Upload Delivery File</button>
      <div id="mDelCurrent" style="font-size:13px;color:#aaa;margin-top:8px"></div>
      <div class="msg" id="mDelMsg"></div>
    </div>

    <!-- History -->
    <div class="mb-sec">
      <div class="mb-sec-title"><i class="fas fa-history"></i>Payment History</div>
      <div id="mHistory"><p style="color:#bbb;font-size:13px">No payment history.</p></div>
    </div>

  </div>
</div>
</div>

<script src="js/admin.js"></script>
<script>
let cBid=null, cFile=null;

function fmt(n){ return parseFloat(n||0).toLocaleString('en-IN',{minimumFractionDigits:0}); }
function showMsg(id,ok,txt){ const el=document.getElementById(id); el.className='msg '+(ok?'ok':'er'); el.textContent=txt; el.style.display='block'; }

function openPanel(b) {
  cBid=b.id; cFile=null;
  document.getElementById('modal').style.display='flex';
  document.getElementById('mBid').textContent='#'+b.id;
  document.getElementById('mTotal').textContent='₹'+fmt(b.total_amount);
  document.getElementById('mAdv').textContent='₹'+fmt(b.advance_paid);
  document.getElementById('mPend').textContent='₹'+fmt(b.pending_amount);
  const ps=document.getElementById('mPayStat');
  ps.textContent=b.payment_status||'unpaid'; ps.className='bdg bdg-'+(b.payment_status||'unpaid');
  const ds=document.getElementById('mDelStat');
  ds.textContent=b.delivery_status||'—'; ds.className='bdg bdg-'+(b.delivery_status||'');
  document.getElementById('mSetTotal').value=b.total_amount||'';
  document.getElementById('mSetAdv').value=b.advance_paid||'';
  calcPend();

  /* Screenshot */
  const sw=document.getElementById('mSSWrap'), vf=document.getElementById('mVerifyForm');
  if(b.payment_screenshot) {
    sw.innerHTML=`<div class="ss-wrap"><img src="../${b.payment_screenshot}" alt="Screenshot" onerror="this.parentElement.innerHTML='<p style=padding:20px;color:#aaa>Image not found</p>'"/></div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
        <a href="../${b.payment_screenshot}" download class="sbtn out" style="font-size:12px;padding:6px 14px"><i class="fas fa-download"></i>Download</a>
        <span class="bdg ${b.payment_verified?'bdg-verified':'bdg-pending'}">${b.payment_verified?'✓ Verified':'⏳ Awaiting Verify'}</span>
      </div>`;
    vf.style.display=b.payment_verified?'none':'block';
  } else { sw.innerHTML='<p style="color:#bbb;font-size:13px;padding:6px 0">No screenshot uploaded yet.</p>'; vf.style.display='none'; }

  /* Delivery */
  document.getElementById('mDelSelect').value=b.delivery_status||'processing';
  document.getElementById('mDelCurrent').textContent=b.delivery_file?'📦 '+b.delivery_file:'';
  document.getElementById('mDelFileName').style.display='none';
  document.getElementById('mDelUpBtn').style.display='none';
  document.getElementById('mDZ').classList.remove('on');

  /* History */
  fetch('payments.php?action=history&booking_id='+b.id)
    .then(r=>r.json()).then(d=>{
      const el=document.getElementById('mHistory');
      if(!d.history||!d.history.length){el.innerHTML='<p style="color:#bbb;font-size:13px">No payment history.</p>';return;}
      el.innerHTML=d.history.map(h=>`
        <div class="hist-row">
          <div><span class="ha">₹${fmt(h.amount)}</span> <span style="font-size:11px;color:#aaa">${h.payment_type}</span></div>
          <div style="text-align:right"><span class="bdg ${h.verified?'bdg-verified':'bdg-pending'}">${h.verified?'✓ Verified':'Pending'}</span><div class="hd">${new Date(h.created_at).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'})}</div></div>
        </div>`).join('');
    }).catch(()=>{});

  /* Clear old messages */
  ['mAmtMsg','mVerMsg','mDelMsg'].forEach(id=>{const el=document.getElementById(id);el.style.display='none';el.textContent='';});
}

function closePanel(){
  document.getElementById('modal').style.display='none'; cBid=null; cFile=null;
}

function calcPend(){
  const t=parseFloat(document.getElementById('mSetTotal').value||0);
  const a=parseFloat(document.getElementById('mSetAdv').value||0);
  const p=Math.max(0,t-a);
  const el=document.getElementById('mPendCalc');
  if(t>0){
    const pct=Math.round(a/t*100);
    const s=a>=t*0.99&&t>0?'<strong style="color:#16a34a">Fully Paid ✓</strong>':a>0?`<strong>Partially Paid (${pct}%)</strong>`:'<strong style="color:#dc2626">Unpaid</strong>';
    el.innerHTML=`Pending Balance: <strong>₹${fmt(p)}</strong> &nbsp;|&nbsp; ${s}`;
  } else el.textContent='';
}

async function saveAmounts(){
  const fd=new FormData();
  fd.append('action','set_amounts'); fd.append('booking_id',cBid);
  fd.append('total_amount',document.getElementById('mSetTotal').value);
  fd.append('advance_paid',document.getElementById('mSetAdv').value);
  const d=await(await fetch('payments.php',{method:'POST',body:fd})).json();
  showMsg('mAmtMsg',d.success,d.success?'✓ Amounts saved! Pending: ₹'+fmt(d.pending):'✗ '+d.error);
  if(d.success){ document.getElementById('mPend').textContent='₹'+fmt(d.pending); document.getElementById('mPayStat').textContent=d.status; document.getElementById('mPayStat').className='bdg bdg-'+d.status; }
}

async function verifyPay(approved){
  const fd=new FormData();
  fd.append('action','verify_payment'); fd.append('booking_id',cBid);
  fd.append('approved',approved); fd.append('amount',document.getElementById('mVerAmt').value||0);
  fd.append('note',document.getElementById('mVerNote').value);
  const d=await(await fetch('payments.php',{method:'POST',body:fd})).json();
  showMsg('mVerMsg',d.success,d.success?(approved?'✓ Payment approved!':'Payment rejected.'):'✗ '+d.error);
  if(d.success) setTimeout(()=>location.reload(),1200);
}

async function saveDelStatus(){
  const fd=new FormData();
  fd.append('action','set_delivery'); fd.append('booking_id',cBid);
  fd.append('delivery_status',document.getElementById('mDelSelect').value);
  const d=await(await fetch('payments.php',{method:'POST',body:fd})).json();
  showMsg('mDelMsg',d.success,d.success?'✓ Delivery status updated!':'✗ '+d.error);
}

function pickFile(inp){ cFile=inp.files[0]; if(!cFile)return; showPickedFile(); }
function dropFile(e){
  e.preventDefault(); document.getElementById('mDZ').classList.remove('on');
  const f=e.dataTransfer?.files[0]; if(!f)return; cFile=f;
  try{const dt=new DataTransfer();dt.items.add(f);document.getElementById('mDelFile').files=dt.files;}catch(err){}
  showPickedFile();
}
function showPickedFile(){
  const fn=document.getElementById('mDelFileName'),ub=document.getElementById('mDelUpBtn');
  fn.textContent='📦 '+cFile.name+' ('+(cFile.size/1024/1024).toFixed(1)+' MB)';
  fn.style.display='block'; ub.style.display='inline-flex';
  document.getElementById('mDZ').classList.add('on');
}
async function uploadDel(){
  if(!cFile)return;
  const btn=document.getElementById('mDelUpBtn');
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>Uploading…'; btn.disabled=true;
  const fd=new FormData();
  fd.append('action','upload_delivery'); fd.append('booking_id',cBid); fd.append('delivery_file',cFile);
  const d=await(await fetch('payments.php',{method:'POST',body:fd})).json();
  showMsg('mDelMsg',d.success,d.success?'✓ File uploaded! Client can now download.':'✗ '+d.error);
  if(d.success) document.getElementById('mDelCurrent').textContent='📦 '+d.file;
  btn.innerHTML='<i class="fas fa-upload"></i>Upload Delivery File'; btn.disabled=false;
}

</script>
</body>
</html>
