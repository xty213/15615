#include <stdio.h>
#include <string.h>
#include "def.h"

// external functions
extern int check_word(char *word);
extern int strtolow(char *s);
extern int FindInsertionPosition(struct KeyRecord * KeyListTraverser, char *Key, int *Found, NUMKEYS NumKeys, int Count);
extern struct PageHdr *FetchPage(PAGENO Page);
PAGENO FindPageNumOfChild(struct PageHdr *PagePtr, struct KeyRecord *KeyListTraverser, char *Key, NUMKEYS NumKeys)

int get_predecessors(char *key, int k, char *result[]) {
    void find_predecessors(PAGENO PageNo, char *key, int k, char *result[], int *cnt);
    
    if (check_word(key) == FALSE) {
        return 0;
    }
    strtolow(key);

    int cnt = 0;
    find_predecessors(ROOT, key, k, result, &cnt);

    printf("found %d predecessors:\n", cnt);
    return cnt;
}

void find_predecessors(PAGENO PageNo, char *key, int k, char *result[], int *cnt) {
    struct PageHdr *PagePtr = FetchPage(PageNo);
    int pos, i, j, Found;
    // if is leaf, try to add records in this leaf node
    if (IsLeaf(PagePtr)) {
        pos = FindInsertionPosition(PagePtr->KeyListPtr, key, &Found, PagePtr->NumKeys, 0);
        for (i = pos; i >= 0 && k > *cnt; i--) {
            struct KeyRecord *keyRecord = PagePtr->KeyListPtr;
            for (j = 0; j < i; j++) {
                keyRecord = keyRecord->Next;
            }
            result[*cnt++] = keyRecord->StoredKey;
        }
    } else if ((IsNonLeaf(PagePtr)) && (PagePtr->NumKeys == 0)) {
        return;
    } else if ((IsNonLeaf(PagePtr)) && (PagePtr->NumKeys > 0)) {
        PAGENO currPage = FindPageNumOfChild(PagePtr, PagePtr->KeyListPtr, key, PagePtr->NumKeys);
        PAGENO prevPage = NULLPAGENO;
        do {
            find_predecessors(currPage, key, k, result, cnt);
            // try to find the prev page
            struct KeyRecord *keyRecord = PagePtr->KeyListPtr;
            while (keyRecord->PgNum != currPage) {
                prevPage = keyRecord->PgNum;
                keyRecord = keyRecord->Next;
            }
            currPage = prevPage;
        } while (*cnt < k);
    }
    FreePage(PagePtr);
    return result;
}
