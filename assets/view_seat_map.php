<?php

include_once ('../db_config.php');
include_once ('../functions.php');
$seatCategoryID = $_POST['seatCategoryID'];
$seat_sql = "SELECT * FROM tbl_theatre_seat_categories WHERE seat_category_id = '$seatCategoryID'";
$seat_result = $db->query($seat_sql);
while ($seat = mysqli_fetch_assoc($seat_result)) {
    $categories = json_decode($seat['category_name']);
    $num_of_rows = json_decode($seat['num_of_rows']);
    $num_of_cols = json_decode($seat['num_of_columns']);
    $max_col = max($num_of_cols);
}


$sql = "SELECT * FROM tbl_seat_maps WHERE seat_category_id = '$seatCategoryID'";
$result = $db->query($sql);
if($result->num_rows>0){
    while($row=$result->fetch_assoc()){
        $seatMapArray = json_decode($row['seat_number']);
    }
}

echo '<div id="amount"></div>';
echo '<div id="seatsBooked"></div>';
echo '<table class="table seat_map_wrap">';
        echo '<tbody>';

        $alpha = range('A', 'Z');
        for ($cat=0; $cat <= count($categories) - 1; $cat++) {
            $catName = get_category_name($db,$categories[$cat]);
            echo '<tr class="category_head"><td colspan="2">' . $catName . '</td></tr>';
            if($num_of_cols[$cat] < $max_col){
                $tdCheck = true;
                $diff = ($max_col - $num_of_cols[$cat]) / 2;
                $addup = '';
                for ($i=1; $i <= $diff ; $i++) { 
                    $addup .= '<span class="seat"></span>';
                }
            }else{
                $tdCheck = false;
            }
            for ($row = $num_of_rows[$cat]; $row >= 1 ; $row--){
                    echo '<tr class="seat_row">';
                        echo '<td>';
                            echo '<div class="seat_row_label">'.$alpha[$num_of_rows[$cat] - $row].'</div>';
                        echo '</td>';
                        echo '<td class="seat_row_seats">';
                            if($tdCheck){
                                echo $addup;
                            }
                            $rate = getTicketRates($db,$categories[$cat],$seatCategoryID);
                            for ($col = 1; $col <= $num_of_cols[$cat]; $col++){
                                $seatNo = $alpha[$num_of_rows[$cat] - $row].$col;
                                echo '<span class="seat">';
                                    echo '<div>';
                                        echo '<a i="seat_label" category="'.$catName.'" row_index="'.$row.'" col_index="'.$col.'" rate="'.$rate.'" seatNo="'.$seatNo.'">'.$seatNo.'</a>';
                                    echo '</div>';
                                echo '</span>';
                            }
                        echo '</td>';
                    echo '</tr>';
                    $last = $alpha[($num_of_rows[$cat] - $row) + 1];
            }
            $alpha = range($last,'Z');
        }
        echo '<tr>
            <td colspan="2">
                <div class="screen_area">
                    <span>THEATRE SCREEN</span>
                </div>
            </td>
        </tr>';
        echo '</tbody>';
    echo '</table>';
    echo '<div id="pay" class="hide pay_btn">Proceed to Pay</div>';

?>

<style>
    .seat_map_wrap {
        margin-top: 10px;
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        background-color: transparent;
        display: inline-block;
        text-align: center;
        padding: 2%;
        width: auto;
        max-width: 100%;
    }
    @media only screen and (max-width: 570px) {
    .seat_map_wrap {
        width: 1040px !important;
        max-width: 1040px !important;
    }}
    tbody{
        width: 100%;
        height: 100%;
    }
    .seat_map_wrap .screen_area span {
        background-color: #6b6b6b;
        display: block;
        border-radius: 5px;
        color: #fff;
        position: relative;
        width: 100%;
        padding: 2px 6px;
        margin-top: 10px;
        user-select: none;
    }
    .seat_map_wrap td {
        border-top: none !important;
        padding: 0 !important;
        line-height: 1.42857143;
    }
    .seat_map_wrap td {
        padding: 0;
        vertical-align: middle;
        border-top: none;
    }
    .category_head{
        width: 100%;
        height: 100%;
        font-weight: bold;
    }
    .seat_map_wrap .seat_row_label {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #4c4d4f;
        font-size: 12px;
        margin-right: 6px;
        margin-top: 4px;
        user-select: none;
    }
    .seat_map_wrap .seat {
        float: left;
        margin: 4px 3px;
        width: 24px;
    }
    .seat_map_wrap .seat a {
        display: inline-block;
        font-family: inherit;
        font-size: 10px;
        line-height: 26px;
        box-shadow: 0 0 0 1px #f09c0b inset;
        height: 26px;
        text-align: center;
        width: 26px;
        border-radius: 26%;
        color: transparent;
        cursor: pointer;
        font-weight: bolder;
        text-decoration: none;
        background: #fff9d8;
        user-select: none;
    }
    .seat_map_wrap .seat a:hover {
        color: transparent;
        background: #f5a825;
        text-decoration: none;
        transition: .5s;
    }
    .seat_map_wrap .seat .selected a{
        background: #f5a825;
        text-decoration: none;
        box-shadow: 0 0 0 1px #f5a825 inset;
    }
    .seat_map_wrap .seat_column_number {
        float: left;
        margin: 4px 3px;
        width: 24px;
        user-select: none;
    }
    .pay_btn{
        width: 100%;
        background-color:blue;
        padding: 2px 5px;
        cursor: pointer;
    }
</style>

<script>
$(document).ready(function () {
    var num_of_rows = Number("<?php echo array_sum($num_of_rows) ?>");
    var num_of_cols = Number("<?php echo max($num_of_cols) ?>");
    var seats_array = new Array(num_of_rows);
    var selectedSeats = [];
    var amount = 0;
    for (var r = 0; r < num_of_rows; r++) {
        seats_array[r] = new Array(num_of_cols);
        for (var c = 0; c < num_of_cols; c++) { 
            seats_array[r][c] = 1;
        }
    }

    var seatsCount = 0;
    var seats_label_array = new Array(num_of_rows);
    for (var i = 0; i < num_of_rows; i++) {
        seats_label_array[i] = new Array(num_of_cols);
        var row_label = String.fromCharCode(65 + (num_of_rows - i - 1));
        var seat_num = 1;
        for (var j = 0; j < num_of_cols; j++) {
            if(seats_array[i][j] == 1) {
                seats_label_array[i][j] = row_label + seat_num;
                seat_num++;
                seatsCount++;
            }else{
                seats_label_array[i][j] = 0;
            }
        }
    }
    $("#seatsArray").val(seats_label_array);
    $("#num_of_seats").val(seatsCount);
    $("a").click(function() {
        if(selectedSeats.length < 10){
            var row_index = $(this).attr("row_index");
            var col_index = $(this).attr("col_index");
            var seatNo = $(this).attr("seatno");
            var rate = parseInt($(this).attr("rate"));
            $(this).parent().toggleClass("selected");
            
            if($(this).parent().hasClass("selected")) {
                seats_array[row_index-1][col_index-1] = 0;
                selectedSeats.push(seatNo);
                amount += rate;
            }else{
                seats_array[row_index-1][col_index-1] = 1;
                selectedSeats = selectedSeats.filter(e => e !== seatNo)
                amount -= rate;
            }
            seatsCount = 0;
            seats_label_array = new Array(num_of_rows);
            for (var i = 0; i < num_of_rows; i++) {
                seats_label_array[i] = new Array(num_of_cols);
                var row_label = String.fromCharCode(65 + (num_of_rows - i - 1));
                var seat_num = 1;
                for (var j = 0; j < num_of_cols; j++) {
                    if(seats_array[i][j] == 1) {
                        seats_label_array[i][j] = row_label + seat_num;
                        seat_num++;
                        seatsCount++;
                    }else{
                        seats_label_array[i][j] = 0;
                    }
                }
            }
            if(selectedSeats.length == 0 && amount== 0){
                $('#amount').addClass('hide');
                $('#seatsBooked').addClass('hide');
                $('#pay').addClass('hide');
            }else{
                $('#amount').removeClass('hide');
                $('#pay').removeClass('hide');
                $('#seatsBooked').removeClass('hide');
                $('#amount').html('Total Amount :- Rs. '+amount);
                $('#seatsBooked').html('Seats - ' + selectedSeats.toString());
            }
            $("#seatsArray").val(seats_label_array);
            $("#num_of_seats").val(seatsCount);
        }
    });
    $('.pay_btn').click(function(){
        var seats = selectedSeats.toString();
        var totalAmount = amount;
        var movie_id = "<?php echo $_SESSION['movieID']; ?>"
        console.log(movie_id)
    })
});
</script>

<?php
echo '<div id="seatsArray"></div>';
echo '<div id="num_of_seats"></div>';
?>
