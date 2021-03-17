.globl start
start:
	.code16
	cli
	cld

	xorw %ax, %ax
	movw %ax,%ds
    movw %ax,%es
    movw %ax,%gs
    movw %ax,%fs
    movw %ax,%ss

seta20.1:
	inb     $0x64,%al
	testb   $0x2,%al
	jnz     seta20.1

	movb    $0xd1,%al
	outb    %al,$0x64

seta20.2:
	inb     $0x64,%al
	testb   $0x2,%al
	jnz     seta20.2

	movb    $0xdf,%al
	outb    %al,$0x60

	lgdt    gdtdesc
	movl    %cr0, %eax
	orl     0x1, %eax
	movl    %eax, %cr0

	ljmp    0x8, $protcseg

	.code32
protcseg:
	movw    0x10, %ax
	movw    %ax, %ds
	movw    %ax, %es
	movw    %ax, %fs
	movw    %ax, %gs
	movw    %ax, %ss

	movl    $start, %esp
	call	loader

spin:
	jmp	spin

gdt:
	SEG_NULL
	SEG(STA_X|STA_R, 0x0, 0xffffffff)
	SEG(STA_W, 0x0, 0xffffffff)

gdtdesc:
	.word   0x17
	.long   gdt
