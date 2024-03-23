<?php

function getGroup($conn)
{
    $perentGroupResult = mysqli_query($conn, "SELECT * FROM groups WHERE id_parent = 0");

    echo "<a href='/'>Все товары</a>";
    echo "<ul>";
    while ($row = mysqli_fetch_assoc($perentGroupResult)) {
        echo "<li><a href='?group={$row['id']}'>{$row['name']}</a> " . getProductCount($conn, $row['id']) . "</li>";

        $selectedGroupId = isset($_GET['group']) ? intval($_GET['group']) : null;
        
        displaySubGroups($conn, $row['id'], $selectedGroupId);     
    }
    echo '</ul>';
}

function getProduct($conn, $groupId) 
{
    $productStmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id_group = ?");
    mysqli_stmt_bind_param($productStmt, "i", $groupId);
    mysqli_stmt_execute($productStmt);
    $productResult = mysqli_stmt_get_result($productStmt);

    while ($row = mysqli_fetch_assoc($productResult)) {
        echo "<p>{$row['name']}</p>";
    }

    mysqli_stmt_close($productStmt);

    $subGroupStmt = mysqli_prepare($conn, "SELECT id FROM groups WHERE id_parent = ?");
    mysqli_stmt_bind_param($subGroupStmt, "i", $groupId);
    mysqli_stmt_execute($subGroupStmt);
    $subGroupResult = mysqli_stmt_get_result($subGroupStmt);

    while ($subGroup = mysqli_fetch_assoc($subGroupResult)) {
        getProduct($conn, $subGroup['id']);
    }

    mysqli_stmt_close($subGroupStmt);
}

function getProductCount($conn, $groupId) 
{
    static $productCountCache = [];

    if (isset($productCountCache[$groupId])) {
        return $productCountCache[$groupId];
    }

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM products WHERE id_group = ?");
    mysqli_stmt_bind_param($stmt, "i", $groupId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $productCount);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $subGroupResult = mysqli_query($conn, "SELECT id FROM groups WHERE id_parent = $groupId");
    while ($subGroup = mysqli_fetch_assoc($subGroupResult)) {
        $productCount += getProductCount($conn, $subGroup['id']);
    }

    $productCountCache[$groupId] = $productCount;
    return $productCount;
}

function displaySubGroups($conn, $parentId, $selectedGroupId = null) 
{
    $childrenGroupResult = mysqli_query($conn, "SELECT * FROM groups WHERE id_parent != 0");

    $rawGroups = [];

    while ($childrenRow = mysqli_fetch_assoc($childrenGroupResult)) {
        $rawGroups[$childrenRow['id']] = $childrenRow;
    }
    
    $groupHierarchy = buildGroupHierarchy($rawGroups, $parentId);
    $allIds = extractIds($groupHierarchy);
    
    $showIds = getParentIds($allIds, $selectedGroupId);

    if($selectedGroupId){
        buildGroupList($conn, $groupHierarchy, $showIds, $parentId, $selectedGroupId);
    }
}

function buildGroupHierarchy($groups, $parentId = 0) 
{
    $branch = [];

    foreach ($groups as $group) {
        if ($group['id_parent'] == $parentId) {
            $children = buildGroupHierarchy($groups, $group['id']);
            if ($children) {
                $group['subgroups'] = $children;
            }
            $branch[$group['id']] = $group;
        }
    }

    return $branch;
}

function extractIds($groups, &$ids = []) 
{

    foreach ($groups as $group) {
        $ids[$group['id']] = $group['id_parent'];
        if (!empty($group['subgroups'])) {
            extractIds($group['subgroups'], $ids);
        }
    }

    return $ids;
}

function getParentIds($array, $selected, &$result = []) 
{
    foreach ($array as $id => $parentId) {
        if ($id == $selected) {
            $result[] = $parentId;
            if ($parentId != 0) {
                getParentIds($array, $parentId, $result);
            }
            break;
        }
    }
    return $result;
}

function buildGroupList($conn, $groups, $showIds, $parentId = 0, $selectedGroupId) 
{
    if (empty($groups)) {
        return;
    }

    echo '<ul>';
    foreach ($groups as $group) {
        if($group['id_parent'] == $selectedGroupId || in_array($group['id_parent'], $showIds)){

            echo '<li>';
            echo '<a href="?group=' . $group['id'] . '">' . $group['name'] . '</a> '. getProductCount($conn, $group['id']);

            if (!empty($group['subgroups'])) {
                buildGroupList($conn, $group['subgroups'], $showIds, $group['id_parent'], $selectedGroupId);
            }

            echo '</li>';

        }
    }
    echo '</ul>';
}