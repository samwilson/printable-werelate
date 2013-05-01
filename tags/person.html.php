
<div class="person infobox main">
<span class="image"><img src="" /></span>
<h2 class="title"><?php echo $fullname ?></h2>
<ul class="bmd">
<li>b. <?php echo $birthDate.' '.$birthPlace ?></li>
<li>d. <?php echo $deathDate.' '.$deathPlace ?></li>
</ul>
</div>


<h3>Parents and siblings</h3>
F. <?php echo $father_link ?><br />
M. <?php echo $mother_link ?>
<ol>
    <?php foreach ($siblings as $sibling): ?>
    <li><?php echo $sibling['link'] ?></li>
    <?php endforeach ?>
</ol>


<?php foreach ($families as $family): ?>
<div class="family">
<h3>Spouse and children</h3>
H. <?php echo $family['husband_link'] ?><br />
W. <?php echo $family['wife_link'] ?>
<ol>
    <?php foreach ($family['children'] as $child): ?>
    <li><?php echo $child['link'] ?></li>
    <?php endforeach ?>
</ol>
</div>
<?php endforeach ?>


<h3>Facts</h3>
<table class="wikitable">
<?php foreach ($facts as $fact): ?>
<tr>
<th><?php echo $fact['type'] ?></th>
<td><span title="<?php echo $fact['sortDate'] ?>"><?php echo $fact['date'] ?></span></td>
<td><?php echo $fact['place'] ?></td>
<td><?php echo $fact['desc'] ?></td>
</tr>
<?php endforeach ?>
</table>

