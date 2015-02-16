#include <stdio.h>
#include <string.h>
#include "def.h"

extern PAGENO treesearch_page_parent(PAGENO PageNo, char *key);

int get_predecessors(char *key, int k, char *result[]) {
    if (check_word(key) == FALSE) {
        return 0;
    }
    strtolow(key);

    int cnt = 0; // found firstPredecessor number
    struct KeyRecord *firstPredecessor = get_predecessor_k(key, k);
    // try to find predecessors
    for (; cnt < k; cnt++) {
        result[cnt] = firstPredecessor->StoredKey;
        firstPredecessor = firstPredecessor->Next;
    }

    printf("found %d predecessors:\n", cnt);
    return cnt;

}

struct KeyRecord *get_predecessor_k(char *key, int k) {
    // find parent page number
    PAGENO parentPage = treesearch_page_parent(ROOT, key);
    struct PageHdr *ParentPagePtr = FetchPage(parentPage);
    // find leaf page number
    PAGENO page = FindPageNumOfChild(ParentPagePtr, ParentPagePtr->KeyListPtr, key, ParentPagePtr->NumKeys);
    struct PageHdr *PagePtr = FetchPage(page);

    // find the insertion position
    int Found, Count, i;
    struct KeyRecord *KeyListTraverser = PagePtr->KeyListPtr;
    int InsertionPosition = FindInsertionPosition(KeyListTraverser, key, &Found, PagePtr->NumKeys, Count);

    for (i = 0; i < InsertionPosition - k; i++)
        KeyListTraverser = KeyListTraverser->Next;
    return KeyListTraverser;
}
